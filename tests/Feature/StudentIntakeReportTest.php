<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentIntakeReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->forgetInstance('settings');
        $this->travelTo(Carbon::parse('2026-10-27 12:00:00', 'Asia/Colombo'));
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('Admin', 'web'));
        $this->actingAs($user);
    }

    private function student(?ClassRoom $class, ?string $date, array $extra = []): Student
    {
        return Student::create($extra + ['name' => 'Intake '.Str::random(6), 'admission_number' => 'REG-'.Str::random(6),
            'joining_date' => $date, 'class_room_id' => $class?->id, 'active' => true, 'alumni' => false]);
    }

    private function report(array $filters = [])
    {
        return $this->get(route('reports.student_intake', $filters));
    }

    private function csvRows($response): array
    {
        $stream = fopen('php://temp', 'r+');
        ob_start();
        $response->baseResponse->sendContent();
        fwrite($stream, preg_replace('/^\xEF\xBB\xBF/', '', ob_get_clean()));
        rewind($stream);
        $rows = [];
        while (($line = fgetcsv($stream, null, ',', '"', '')) !== false) {
            $rows[] = $line;
        }
        fclose($stream);

        return $rows;
    }

    public function test_intake_counts_use_inclusive_joining_dates_and_keep_inactive_and_alumni_in_historical_totals(): void
    {
        $first = ClassRoom::create(['name' => 'Nursery']);
        $second = ClassRoom::create(['name' => 'Grade 1']);
        $start = $this->student($first, '2026-10-01', ['name' => 'First day pupil']);
        $end = $this->student($second, '2026-10-31', ['name' => 'Last day pupil']);
        $this->student($first, '2026-10-15', ['active' => false]);
        $this->student(null, '2026-10-15', ['alumni' => true, 'active' => false]);
        $this->student($first, '2026-09-30', ['name' => 'Before period']);
        $this->student($first, '2026-11-01', ['name' => 'After period']);
        $this->student($first, null, ['name' => 'Missing joining date']);
        $response = $this->report(['from' => '2026-10-01', 'to' => '2026-10-31'])->assertOk()
            ->assertViewHas('total', 4)->assertViewHas('intakeDays', 3)->assertSee($start->admission_number)->assertSee($end->admission_number)
            ->assertDontSee('Before period')->assertDontSee('After period')->assertDontSee('Missing joining date');
        $this->assertSame(4, $response->viewData('classCounts')->sum('total'));
        $this->report()->assertOk()->assertViewHas('filters', fn ($f) => $f['from'] === '2026-10-01' && $f['to'] === '2026-10-27')
            ->assertViewHas('total', 3)->assertDontSee('Last day pupil');
        $this->get(route('reports.index'))->assertOk()->assertSee('Student Intake');
    }

    public function test_class_status_search_sort_and_unassigned_filters_match_the_list_and_summary(): void
    {
        $class = ClassRoom::create(['name' => 'Preschool']);
        $active = $this->student($class, '2026-10-03', ['name' => 'Asha Intake', 'admission_number' => 'REG-100']);
        $this->student($class, '2026-10-02', ['name' => 'Inactive pupil', 'active' => false]);
        $this->student($class, '2026-10-01', ['alumni' => true]);
        $this->student(null, '2026-10-05', ['name' => 'Unassigned intake']);
        $this->report(['class_room_id' => $class->id, 'q' => 'REG-100', 'status' => 'active'])->assertOk()->assertViewHas('total', 1)->assertSee($active->name)->assertDontSee('Inactive pupil');
        foreach (['inactive', 'alumni'] as $status) {
            $this->report(['status' => $status])->assertOk()->assertViewHas('total', 1);
        }
        $this->report(['class_room_id' => 'unassigned'])->assertOk()->assertViewHas('total', 1)->assertSee('Unassigned intake');
        $this->report(['sort' => 'date_asc'])->assertOk()->assertViewHas('items', fn ($items) => $items->first()->joining_date->toDateString() === '2026-10-01');
        $this->report(['sort' => 'name_asc'])->assertOk()->assertViewHas('items', fn ($items) => $items->first()->name === 'Asha Intake');
        $this->report(['status' => '', 'per_page' => '', 'sort' => ''])->assertOk()->assertViewHas('total', 4);
    }

    public function test_pagination_keeps_filters_and_downloads_include_every_match_with_registration_number_first(): void
    {
        $class = ClassRoom::create(['name' => 'Grade 2']);
        foreach (range(1, 27) as $i) {
            $this->student($class, '2026-10-05', ['name' => sprintf('Intake pupil %02d', $i), 'admission_number' => sprintf('REG-%03d', $i)]);
        }
        $filters = ['from' => '2026-10-05', 'to' => '2026-10-05', 'class_room_id' => $class->id, 'q' => 'Intake pupil', 'sort' => 'name_asc'];
        $response = $this->report($filters + ['page' => 2])->assertOk()->assertViewHas('total', 27)->assertSee('REG-027')->assertDontSee('REG-001');
        $this->assertCount(2, $response->viewData('items'));
        parse_str(parse_url($response->viewData('items')->url(1), PHP_URL_QUERY), $query);
        $this->assertSame('Intake pupil', $query['q']);
        $this->assertSame('2026-10-05', $query['from']);
        $rows = $this->csvRows($this->report($filters + ['page' => 2, 'download' => 'csv'])->assertOk()->assertDownload());
        $this->assertCount(28, $rows);
        $this->assertSame('Registration number', $rows[0][0]);
        $this->assertSame('REG-001', $rows[1][0]);
        $this->assertSame('REG-027', $rows[27][0]);
        $engine = app('dompdf.wrapper');
        Pdf::shouldReceive('loadView')->once()->withArgs(fn ($view, $data) => $view === 'reports.student-intake-pdf' && $data['items']->count() === 27 && $data['total'] === 27)
            ->andReturnUsing(fn ($view, $data) => $engine->loadView($view, $data));
        $pdf = $this->report($filters + ['page' => 2, 'download' => 'pdf'])->assertOk()->assertDownload();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
    }

    public function test_validation_rejects_bad_ranges_filters_and_download_formats(): void
    {
        foreach ([['from' => '2026-10-28', 'to' => '2026-10-01'], ['from' => '2026-02-30'], ['to' => 'invalid'],
            ['class_room_id' => 99999], ['status' => 'invalid'], ['per_page' => 999], ['sort' => 'id'], ['download' => 'php']] as $filters) {
            $this->getJson(route('reports.student_intake', $filters))->assertUnprocessable();
        }
    }

    public function test_permissions_guard_report_and_downloads_independently(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('reports.view');
        $this->actingAs($viewer);
        $this->report()->assertOk()->assertDontSee('Download PDF')->assertDontSee('Download CSV');
        foreach (['pdf', 'csv'] as $download) {
            $this->report(['download' => $download])->assertForbidden();
        }
        $viewer->givePermissionTo('reports.download');
        $this->report(['download' => 'csv'])->assertOk()->assertDownload();
        $this->actingAs(User::factory()->create());
        $this->report()->assertForbidden();
    }

    public function test_search_treats_wildcards_literally_and_csv_names_cannot_execute_as_formulas(): void
    {
        $this->student(null, '2026-10-02', ['name' => '=SUM(1,2)', 'admission_number' => '+123']);
        $this->student(null, '2026-10-02', ['name' => '100% student']);
        $this->student(null, '2026-10-02', ['name' => 'Ordinary student']);
        $this->report(['q' => '%'])->assertOk()->assertViewHas('total', 1)->assertSee('100% student');
        $rows = $this->csvRows($this->report(['q' => '+123', 'download' => 'csv'])->assertOk());
        $this->assertSame("'+123", $rows[1][0]);
        $this->assertSame("'=SUM(1,2)", $rows[1][1]);
        $this->report(['q' => 'not found'])->assertOk()->assertViewHas('total', 0)->assertViewHas('intakeDays', 0)->assertSee('No student intakes match');
        $this->report(['q' => 'not found', 'download' => 'pdf'])->assertOk()->assertDownload();
    }
}
