<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\StudentPromotionHistory;
use App\Models\User;
use App\Services\Billing\ClassFeePaymentService;
use App\Services\Billing\ClassFeeService;
use App\Services\CashBalanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClassFeeStudentRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->forgetInstance('settings');
        $this->travelTo(Carbon::parse('2026-10-26 12:00:00'));
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('Admin', 'web'));
        $this->actingAs($user);
    }

    private function school(): ClassRoom
    {
        $class = ClassRoom::create(['name' => 'Grade '.Str::random(5), 'active' => true]);
        app(ClassFeeService::class)->configure($class, ['exam_fee' => 1000, 'exam_terms' => [
            ['start' => '2026-01-01', 'end' => '2026-04-30'], ['start' => '2026-05-01', 'end' => '2026-08-31'],
            ['start' => '2026-09-01', 'end' => '2026-12-31'],
        ], 'facility_fee' => 2000, 'facility_fee_start_date' => '2026-01-15']);

        return $class;
    }

    private function student(?ClassRoom $class, array $extra = []): Student
    {
        return Student::create($extra + ['name' => 'Pupil '.Str::random(6), 'admission_number' => Str::random(8),
            'class_room_id' => $class?->id, 'joining_date' => '2025-01-01', 'active' => true, 'alumni' => false]);
    }

    private function register(array $filters = [])
    {
        return $this->get(route('class-fees.index', $filters + ['view' => 'students', 'year' => 2026]))->assertOk();
    }

    private function pay(ClassRoom $class, Student $student, string $date = '2026-01-01', array $extra = [], string $type = 'exam')
    {
        $rule = $class->feeSchedules()->where('fee_type', $type)->firstOrFail();

        return app(ClassFeePaymentService::class)->collect($class, $type, $extra + [
            'student_id' => $student->id, 'cycle' => $rule->id.':'.$date, 'amount' => $rule->amount,
            'submission_id' => (string) Str::uuid(), 'paid_at' => '2026-10-26', 'payment_method' => 'cash',
        ], auth()->user());
    }

    private function context(ClassRoom $class, Student $student, string $date = '2026-01-01', string $type = 'exam'): array
    {
        $rule = $class->feeSchedules()->where('fee_type', $type)->firstOrFail();

        return ['view' => 'students', 'year' => (int) substr($date, 0, 4), 'class_room_id' => $class->id,
            'student_id' => $student->id, 'cycle' => $rule->id.':'.$date, 'include_inactive' => 1,
            'include_former' => 1, 'include_facility' => 1];
    }

    private function formValues(string $html): array
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $values = [];
        foreach ((new \DOMXPath($dom))->query('//form//input[@type="hidden"]') as $input) {
            $values[$input->getAttribute('name')] = $input->getAttribute('value');
        }

        return $values;
    }

    private function csvRows($response): array
    {
        ob_start();
        $response->baseResponse->sendContent();
        $csv = ob_get_clean();
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, preg_replace('/^\xEF\xBB\xBF/', '', $csv));
        rewind($stream);
        $rows = [];
        while (($line = fgetcsv($stream, null, ',', '"', '')) !== false) {
            $rows[] = $line;
        }
        fclose($stream);

        return $rows;
    }

    public function test_all_students_includes_classes_without_fees_and_unassigned_students(): void
    {
        $class = $this->school();
        $ordinary = $this->student($class, ['name' => 'Ordinary pupil']);
        $empty = ClassRoom::create(['name' => 'New class with no fees']);
        $noFees = $this->student($empty, ['name' => 'No fee pupil']);
        $unassigned = $this->student(null, ['name' => 'Unassigned pupil']);
        $response = $this->register();
        $response->assertSee($ordinary->name)->assertSee($noFees->name)->assertSee($unassigned->name)
            ->assertSee('No class assigned')->assertSee('No selected fees in this period.')
            ->assertSee('Download CSV')->assertViewHas('studentCount', 3);
        $this->register(['class_room_id' => $class->id])->assertSee($ordinary->name)->assertDontSee($noFees->name)->assertDontSee($unassigned->name);
        $this->register(['exam_selection' => 1, 'include_facility' => 0])->assertViewHas('studentCount', 3)->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 0.0);
    }

    public function test_live_search_filters_rows_totals_and_terms_without_returning_a_whole_layout(): void
    {
        $class = $this->school();
        $alice = $this->student($class, ['name' => 'Alice Register']);
        $bob = $this->student($class, ['name' => 'Bob Register']);
        $this->pay($class, $alice);
        $response = $this->getJson(route('class-fees.index', ['view' => 'students', 'year' => 2026, 'fragment' => 1,
            'q' => $alice->admission_number, 'exam_selection' => 1, 'exam_dates' => ['2026-01-01'], 'include_facility' => 0]))->assertOk()->assertJsonPath('count', 1);
        $this->assertStringContainsString('Alice Register', $response->json('html'));
        $this->assertStringNotContainsString('Bob Register', $response->json('html'));
        $this->assertStringNotContainsString('<html', $response->json('html'));
        $this->assertStringContainsString('Paid Rs 1,000.00', $response->json('html'));
        $this->assertStringContainsString('q='.urlencode($alice->admission_number), $response->json('url'));
        $this->assertStringContainsString('2026-09-01', $response->json('terms_html'));
        $this->register(['status' => 'paid', 'exam_selection' => 1, 'exam_dates' => ['2026-01-01'], 'include_facility' => 0])
            ->assertSee($alice->name)->assertDontSee($bob->name);
        $this->register(['status' => 'unpaid', 'exam_selection' => 1, 'exam_dates' => ['2026-01-01'], 'include_facility' => 0])
            ->assertDontSee($alice->name)->assertSee($bob->name);
        $this->register(['q' => 'Missing pupil'])->assertViewHas('studentCount', 0)->assertSee('No students match these filters.');
    }

    public function test_pagination_limits_display_but_csv_and_pdf_include_every_filtered_match(): void
    {
        $class = $this->school();
        foreach (range(1, 28) as $number) {
            $this->student($class, ['name' => sprintf('Register pupil %02d', $number)]);
        }
        $response = $this->register(['type' => 'facility']);
        $this->assertCount(25, $response->viewData('paginator')->items());
        $response->assertViewHas('studentCount', 28)->assertSee('Register pupil 01')->assertDontSee('Register pupil 28');
        $this->register(['type' => 'facility', 'page' => 2])->assertSee('Register pupil 28')->assertDontSee('Register pupil 01');
        $response = $this->register(['type' => 'facility', 'download' => 'csv', 'page' => 2])->assertDownload('class-fee-students.csv');
        $rows = $this->csvRows($response);
        $this->assertCount(29, $rows);
        $this->assertSame('Register pupil 01', $rows[1][0]);
        $this->assertSame('Register pupil 28', $rows[28][0]);
        $engine = app('dompdf.wrapper');
        Pdf::shouldReceive('loadView')->once()->withArgs(fn ($view, $data) => $view === 'class-fees.pdf' && $data['sections']->sum(fn ($section) => $section['rows']->count()) === 28)->andReturnUsing(fn ($view, $data) => $engine->loadView($view, $data));
        $this->register(['type' => 'facility', 'page' => 2, 'per_page' => 10, 'pdf' => 1])->assertDownload();
    }

    public function test_pay_popup_saves_with_json_and_preserves_register_filters_on_normal_submission(): void
    {
        $class = $this->school();
        $student = $this->student($class);
        $context = $this->context($class, $student);
        $panel = $this->get(route('class-fees.index', $context + ['action_panel' => 'pay']))->assertOk()->assertSee('Include in revenue & ledger');
        $data = $this->formValues($panel->getContent()) + ['amount' => 400, 'paid_at' => '2026-10-26', 'payment_method' => 'cash',
            'return_filters' => ['view' => 'students', 'q' => $student->name, 'include_facility' => 0, 'year' => 2026]];
        $response = $this->postJson(route('class-fees.store', ['classroom' => $class, 'type' => 'exam']), $data)->assertOk()->assertJsonStructure(['message', 'receipt_url']);
        $this->assertStringContainsString('/receipt', $response->json('receipt_url'));
        $this->assertSame(400.0, app(CashBalanceService::class)->balanceOn('2026-10-26'));
        $this->register(['q' => $student->name, 'include_facility' => 0])->assertViewHas('tracker', fn ($tracker) => $tracker['paid'] === 400.0 && $tracker['due'] === 2600.0);
        $data['submission_id'] = (string) Str::uuid();
        $data['amount'] = 300;
        $response = $this->post(route('class-fees.store', ['classroom' => $class, 'type' => 'exam']), $data)->assertSessionHasNoErrors();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('students', $query['view']);
        $this->assertSame($student->name, $query['q']);
        $this->assertSame('0', $query['include_facility']);
    }

    public function test_make_unpaid_popup_requires_reason_reopens_fee_and_keeps_unselected_term(): void
    {
        $class = $this->school();
        $student = $this->student($class);
        $payment = $this->pay($class, $student);
        $other = $this->pay($class, $student, '2026-05-01', ['record_revenue' => 0]);
        $panel = $this->get(route('class-fees.index', $this->context($class, $student) + ['action_panel' => 'unpaid']))->assertOk()->assertSee($payment->bill_no)->assertSee('Reason for making this fee unpaid');
        $data = $this->formValues($panel->getContent());
        $this->postJson(route('class-fees.bulk.unpaid'), $data)->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->assertFalse($payment->fresh()->isCancelled());
        $data['reason'] = 'Wrong student recorded';
        $this->postJson(route('class-fees.bulk.unpaid'), $data)->assertOk()->assertJsonStructure(['message']);
        $this->assertTrue($payment->fresh()->isCancelled());
        $this->assertFalse($other->fresh()->isCancelled());
        $this->assertSame('Wrong student recorded', $payment->fresh()->cancel_reason);
        $this->assertSame(0.0, app(CashBalanceService::class)->balanceOn('2026-10-26'));
    }

    public function test_stale_popup_and_adjustments_cannot_cancel_unreviewed_receipts(): void
    {
        $class = $this->school();
        $student = $this->student($class);
        $first = $this->pay($class, $student, extra: ['amount' => 400]);
        $panel = $this->get(route('class-fees.index', $this->context($class, $student) + ['action_panel' => 'unpaid']))->assertOk();
        $second = $this->pay($class, $student, extra: ['amount' => 600]);
        $data = $this->formValues($panel->getContent()) + ['reason' => 'Wrong payment'];
        $this->postJson(route('class-fees.bulk.unpaid'), $data)->assertUnprocessable()->assertJsonValidationErrors('lines.0');
        $this->assertFalse($first->fresh()->isCancelled());
        $this->assertFalse($second->fresh()->isCancelled());
        $first->adjustments()->create(['student_id' => $student->id, 'type' => 'waiver', 'amount' => 20]);
        $this->get(route('class-fees.index', $this->context($class, $student) + ['action_panel' => 'unpaid']))->assertOk()->assertSee('refunds or waivers')->assertDontSee('name="reason"', false);
    }

    public function test_bank_and_cheque_and_historical_payments_keep_the_existing_accounting_rules(): void
    {
        $class = $this->school();
        $student = $this->student($class);
        $context = $this->context($class, $student);
        $base = ['return_to' => 'students', 'student_id' => $student->id, 'cycle' => $context['cycle'], 'paid_at' => '2026-10-26'];
        $route = route('class-fees.store', ['classroom' => $class, 'type' => 'exam']);
        $this->postJson($route, $base + ['submission_id' => (string) Str::uuid(), 'amount' => 100, 'payment_method' => 'bank_transfer'])->assertUnprocessable()->assertJsonValidationErrors('bank_name');
        $this->postJson($route, $base + ['submission_id' => (string) Str::uuid(), 'amount' => 200, 'payment_method' => 'bank_transfer', 'bank_name' => 'Test bank'])->assertOk();
        $this->postJson($route, $base + ['submission_id' => (string) Str::uuid(), 'amount' => 300, 'payment_method' => 'cash', 'record_revenue' => 0])->assertOk()->assertJsonPath('receipt_url', null);
        $cheque = ['submission_id' => (string) Str::uuid(), 'amount' => 500, 'payment_method' => 'cheque', 'cheque_date' => '2026-10-27', 'cheque_number' => '12345', 'cheque_bank' => 'Bank'];
        $this->postJson($route, $base + $cheque + ['record_revenue' => 0])->assertUnprocessable();
        $this->postJson($route, $base + $cheque)->assertOk();
        $this->assertDatabaseCount('revenues', 2);
        $this->assertDatabaseCount('class_fee_payment_histories', 1);
        $this->assertDatabaseHas('revenues', ['amount' => 500, 'payment_status' => 'hold']);
        $this->assertSame(0.0, app(CashBalanceService::class)->balanceOn('2026-10-26'));
        $this->register(['status' => 'pending'])->assertViewHas('studentCount', 1);
    }

    public function test_permissions_hide_actions_and_guard_previews_exports_and_writes(): void
    {
        $class = $this->school();
        $student = $this->student($class);
        $this->pay($class, $student);
        $context = $this->context($class, $student);
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('class_fees.view');
        $this->actingAs($viewer);
        $this->register()->assertDontSee('data-register-action=', false)->assertDontSee('Download PDF')->assertDontSee('Download CSV');
        foreach (['pay', 'unpaid'] as $action) {
            $this->get(route('class-fees.index', $context + ['action_panel' => $action]))->assertForbidden();
        }
        $this->get(route('class-fees.index', ['view' => 'students', 'download' => 'csv']))->assertForbidden();
        $this->get(route('class-fees.index', ['view' => 'students', 'pdf' => 1]))->assertForbidden();
        $this->postJson(route('class-fees.bulk.unpaid'), [])->assertForbidden();
        $viewer->givePermissionTo('reports.download');
        $this->register(['download' => 'csv'])->assertDownload();
        $this->actingAs(User::factory()->create())->get(route('class-fees.index', ['view' => 'students', 'fragment' => 1]))->assertForbidden();
    }

    public function test_inactive_former_and_all_year_filters_are_shared_with_downloads(): void
    {
        $original = $this->school();
        $destination = $this->school();
        $student = $this->student($original, ['name' => 'Former inactive pupil']);
        $this->pay($original, $student);
        $move = StudentPromotionHistory::create(['student_id' => $student->id, 'from_class_room_id' => $original->id, 'to_class_room_id' => $destination->id, 'action' => 'transfer']);
        $move->forceFill(['created_at' => Carbon::parse('2026-08-01')])->save();
        $student->update(['class_room_id' => $destination->id, 'active' => false]);
        $this->register()->assertViewHas('studentCount', 0);
        $this->register(['class_room_id' => $original->id, 'include_inactive' => 1])->assertViewHas('studentCount', 0);
        $filters = ['class_room_id' => $original->id, 'include_inactive' => 1, 'include_former' => 1, 'all_years' => 1, 'q' => 'Former inactive', 'exam_selection' => 1, 'exam_dates' => ['2026-01-01'], 'include_facility' => 0];
        $this->register($filters)->assertSee($student->name)->assertSee('Former class')->assertViewHas('studentCount', 1)->assertViewHas('tracker', fn ($tracker) => $tracker['paid'] === 1000.0);
        $rows = $this->csvRows($this->register($filters + ['download' => 'csv']));
        $this->assertCount(2, $rows);
        $this->assertSame('1000', $rows[1][8]);
        $this->assertSame('Paid', $rows[1][13]);
    }

    public function test_csv_handles_no_fees_and_neutralizes_formula_names_and_bad_return_filters_prevent_writes(): void
    {
        $student = $this->student(null, ['name' => '=SUM(1,2)', 'admission_number' => '+12345']);
        $rows = $this->csvRows($this->register(['download' => 'csv']));
        $this->assertSame("'=SUM(1,2)", $rows[1][0]);
        $this->assertSame("'+12345", $rows[1][1]);
        $this->assertSame('No selected fees', $rows[1][13]);
        $class = $this->school();
        $student->update(['class_room_id' => $class->id]);
        $context = $this->context($class, $student);
        $this->postJson(route('class-fees.store', ['classroom' => $class, 'type' => 'exam']), [
            'return_to' => 'students', 'return_filters' => ['status' => 'invalid'], 'student_id' => $student->id, 'cycle' => $context['cycle'],
            'submission_id' => (string) Str::uuid(), 'amount' => 100, 'paid_at' => '2026-10-26', 'payment_method' => 'cash',
        ])->assertUnprocessable()->assertJsonValidationErrors('return_filters.status');
        $this->assertDatabaseCount('revenues', 0);
        $this->register(['pdf' => 1])->assertDownload();
    }

    public function test_numbered_pagination_and_page_sizes_keep_filters_and_export_all_matches(): void
    {
        $class = $this->school();
        foreach (range(1, 75) as $number) {
            $this->student($class, ['name' => sprintf('Paged pupil %02d', $number)]);
        }
        $this->student($class, ['name' => 'Not a match']);
        $filters = ['type' => 'facility', 'class_room_id' => $class->id, 'q' => 'Paged pupil',
            'status' => 'unpaid', 'year' => 2026, 'per_page' => 10, 'page' => 4];
        $response = $this->register($filters)->assertSee('Page 4 of 8')->assertSee('Showing 31–40 of 75')
            ->assertSee('Paged pupil 31')->assertSee('Paged pupil 40')->assertDontSee('Paged pupil 30')->assertDontSee('Paged pupil 41')
            ->assertSee('Rows per page')->assertSee('Student pages (top)')->assertSee('Student pages (bottom)');
        $this->assertCount(10, $response->viewData('paginator')->items());
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        $links = (new \DOMXPath($dom))->query('//a[@data-register-page]');
        $this->assertGreaterThan(4, $links->length);
        foreach ($links as $link) {
            parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY), $query);
            $this->assertSame('10', $query['per_page']);
            $this->assertSame('Paged pupil', $query['q']);
            $this->assertSame((string) $class->id, $query['class_room_id']);
            $this->assertSame('facility', $query['type']);
            $this->assertSame('unpaid', $query['status']);
        }
        $rows = $this->csvRows($this->register($filters + ['download' => 'csv']));
        $this->assertCount(76, $rows);
        $this->register(array_replace($filters, ['page' => 999]))->assertSee('Page 8 of 8')->assertSee('Paged pupil 75');
        $this->register(array_replace($filters, ['per_page' => 100]))->assertSee('Page 1 of 1')->assertSee('Paged pupil 01')->assertSee('Paged pupil 75');
        $fragment = $this->getJson(route('class-fees.index', ['view' => 'students', 'fragment' => 1] + $filters))->assertOk();
        $this->assertStringContainsString('Page 4 of 8', $fragment->json('html'));
        $this->assertStringContainsString('per_page=10', $fragment->json('url'));
        $this->assertStringContainsString('page=4', $fragment->json('url'));
        foreach ([0, 11, 1000, -1] as $invalid) {
            $this->getJson(route('class-fees.index', ['view' => 'students', 'per_page' => $invalid]))->assertUnprocessable()->assertJsonValidationErrors('per_page');
        }
    }
}
