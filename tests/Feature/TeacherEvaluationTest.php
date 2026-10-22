<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherEvaluation;
use App\Models\User;
use App\Services\TeacherEvaluations\EvaluationExport;
use App\Services\TeacherEvaluations\EvaluationForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TeacherEvaluationTest extends TestCase
{
    use RefreshDatabase;

    private function hr(): User
    {
        $user = User::factory()->create();
        $user->assignRole('HR admin');

        return $user;
    }

    private function annual(Teacher $teacher, array $overrides = []): array
    {
        return array_replace([
            'type' => 'annual', 'teacher_id' => $teacher->id, 'appraisal_year' => 2026,
            'acknowledge_incomplete' => true, 'status' => 'completed', 'section' => 'Primary', 'subject_class' => 'Grade 2',
            'evaluator' => 'Head of Primary', 'review_date' => '2026-10-20',
            'period_start' => '2026-01-01', 'period_end' => '2026-12-31',
            'ratings' => array_fill(1, 13, 4), 'remarks' => ['1' => 'Attendance register checked.'],
            'teacher_comments' => 'I welcome the coaching plan.',
        ], $overrides);
    }

    private function event(Teacher $teacher, array $overrides = []): array
    {
        return array_replace([
            'type' => 'event', 'teacher_id' => $teacher->id, 'event_name' => 'Open Day',
            'event_date' => '2026-10-15', 'responsibility' => 'Coordinate the science exhibition.',
            'acknowledge_incomplete' => true, 'status' => 'completed', 'section' => 'Secondary', 'evaluator' => 'Principal',
            'review_date' => '2026-10-20',
            'evidence' => [['area' => 'School events', 'examples' => 'Prepared all displays on time.', 'strengths' => 'Organisation', 'reviewer' => 'Principal', 'review_date' => '2026-11-01']],
            'development' => [['goal' => 'Delegate event tasks', 'training' => 'Mentoring session', 'measure' => 'Team members lead two displays', 'target_date' => '2026-12-01', 'support' => 'Deputy principal', 'notes' => 'Review in December']],
        ], $overrides);
    }

    private function createAnnual(?Teacher $teacher = null, array $overrides = []): TeacherEvaluation
    {
        $teacher ??= Teacher::factory()->create();
        $this->post(route('teacher-evaluations.store'), $this->annual($teacher, $overrides))->assertSessionHasNoErrors();

        return TeacherEvaluation::latest('id')->firstOrFail();
    }

    public function test_hr_role_has_only_the_intended_permissions_and_landing_page(): void
    {
        $user = $this->hr();
        $this->assertEqualsCanonicalizing(EvaluationForm::HR_PERMISSIONS, $user->getAllPermissions()->pluck('name')->all());
        $this->assertSame('teacher-evaluations.index', $user->landingRoute());
        $this->actingAs($user)->get('/')->assertRedirect(route('teacher-evaluations.index'));
        $this->get(route('teacher-evaluations.index'))->assertOk()->assertSee('Teacher Evaluations')
            ->assertDontSee('href="'.route('revenue.items.index').'"', false)
            ->assertDontSee('href="'.route('expense.items.index').'"', false)
            ->assertDontSee('href="'.route('users.index').'"', false);
        $this->get(route('teacher-evaluations.instructions'))->assertOk()->assertSee('whole number from 1 to 5');
    }

    public function test_hr_cannot_open_other_permission_protected_modules_or_change_profiles(): void
    {
        $this->actingAs($this->hr())->withoutMiddleware(SubstituteBindings::class);
        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            $permissions = collect($route->gatherMiddleware())->filter(fn ($m) => str_starts_with($m, 'permission:') || str_starts_with($m, 'role_or_permission:'));
            if (! $permissions->count() || ! $route->getName() || str_starts_with($route->getName(), 'teacher-evaluations.') || str_starts_with($route->getName(), 'hr.')) {
                continue;
            }
            $this->call($route->methods()[0], route($route->getName(), array_fill_keys($route->parameterNames(), 99999)))->assertForbidden();
            $checked++;
        }
        $this->assertGreaterThan(150, $checked);
    }

    public function test_directories_select_only_nonfinancial_fields_and_are_read_only(): void
    {
        $teacher = Teacher::factory()->create(['salary_amount' => 987654.32, 'name' => 'HR Teacher']);
        $student = Student::factory()->create(['monthly_fee' => 65432.10, 'due_amount' => 55555, 'name' => 'HR Student']);
        $this->actingAs($this->hr());
        $this->get(route('hr.teachers.index'))->assertOk()->assertSee('HR Teacher')->assertDontSee('987,654')->assertViewHas('records', fn ($rows) => ! array_key_exists('salary_amount', $rows->first()->getAttributes()));
        $this->get(route('hr.teachers.show', $teacher))->assertOk()->assertSee('New annual appraisal')->assertDontSee('salary_amount')->assertViewHas('person', fn ($person) => ! array_key_exists('salary_components', $person->getAttributes()));
        $this->get(route('hr.students.index'))->assertOk()->assertSee('HR Student')->assertViewHas('records', fn ($rows) => ! array_key_exists('monthly_fee', $rows->first()->getAttributes()));
        $this->get(route('hr.students.show', $student))->assertOk()->assertDontSee('due_amount')->assertViewHas('person', fn ($person) => ! array_key_exists('due_amount', $person->getAttributes()));
        $this->put(route('teachers.update', $teacher), ['salary_amount' => 1])->assertForbidden();
        $this->put(route('students.update', $student), ['name' => 'Changed'])->assertForbidden();
    }

    public function test_annual_form_complete_weighted_score_history_and_duplicate_year(): void
    {
        $teacher = Teacher::factory()->create(['name' => 'Annual Teacher']);
        $this->actingAs($this->hr())->get(route('teacher-evaluations.create', ['teacher_id' => $teacher->id]))->assertOk()->assertSee('Attendance and punctuality');
        $record = $this->createAnnual($teacher);
        $this->assertSame(80.0, $record->score);
        $this->assertSame('Exceeds expectations', $record->band);
        $this->assertCount(13, $record->criteria);
        $this->assertSame(100, array_sum(array_column($record->criteria, 'weight')));
        $this->assertNotNull($record->completed_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'teacher_evaluation.created', 'auditable_id' => $record->id]);
        $this->get(route('teacher-evaluations.show', $record))->assertOk()->assertSee('80.00 / 100')->assertSee('Attendance register checked.');
        $this->get(route('teacher-evaluations.edit', $record))->assertOk()->assertSee('Save completed report');
        $this->post(route('teacher-evaluations.store'), $this->annual($teacher))->assertSessionHasErrors('appraisal_year');
        $this->post(route('teacher-evaluations.store'), $this->annual($teacher, ['appraisal_year' => 2027]))->assertSessionHasNoErrors();
        $teacher->update(['name' => 'Renamed Teacher']);
        $this->assertSame('Annual Teacher', $record->fresh()->teacher_name);
    }

    public function test_drafts_and_completed_reports_can_keep_missing_review_details_after_acknowledgement(): void
    {
        $teacher = Teacher::factory()->create();
        $this->actingAs($this->hr());
        $data = $this->annual($teacher, ['status' => 'draft', 'ratings' => [1 => 5], 'evaluator' => null, 'review_date' => null, 'period_start' => null, 'period_end' => null, 'section' => null]);
        $this->post(route('teacher-evaluations.store'), $data)->assertSessionHasNoErrors();
        $record = TeacherEvaluation::firstOrFail();
        $this->assertNull($record->score);
        $this->assertNull($record->criteria[1]['rating']);
        $this->assertNull($record->completed_at);
        $data['version'] = 1;
        $data['status'] = 'completed';
        $this->put(route('teacher-evaluations.update', $record), $data)->assertSessionHasNoErrors();
        $this->assertSame('completed', $record->fresh()->status);
        $this->assertNull($record->fresh()->score);
        $valid = $this->annual($teacher, ['version' => 2]);
        $this->put(route('teacher-evaluations.update', $record), $valid)->assertSessionHasNoErrors();
        $this->assertSame('completed', $record->fresh()->status);
        $this->assertSame(3, $record->fresh()->version);
    }

    public function test_input_validation_rejects_out_of_range_ratings_and_untrusted_scores(): void
    {
        $teacher = Teacher::factory()->create();
        $this->actingAs($this->hr());
        foreach ([0, 6, 2.5, 'bad'] as $rating) {
            $this->post(route('teacher-evaluations.store'), $this->annual($teacher, ['ratings' => array_replace(array_fill(1, 13, 4), [1 => $rating])]))->assertSessionHasErrors('ratings.1');
        }
        $this->post(route('teacher-evaluations.store'), $this->annual($teacher, ['period_end' => '2025-01-01']))->assertSessionHasErrors('period_end');
        $record = $this->createAnnual($teacher, ['score' => 100, 'created_by' => 999, 'criteria' => [['weight' => 500]]]);
        $this->assertSame(80.0, $record->score);
        $this->assertSame(auth()->id(), $record->created_by);
    }

    public function test_event_reviews_support_multiple_events_and_repeatable_rows(): void
    {
        $teacher = Teacher::factory()->create();
        $this->actingAs($this->hr())->get(route('teacher-evaluations.create', ['type' => 'event']))->assertOk()->assertSee('Assigned responsibility / duties');
        $data = $this->event($teacher);
        $data['evidence'][] = ['area' => 'Teamwork', 'examples' => 'Supported two colleagues', 'reviewer' => 'Deputy Principal'];
        $data['evidence'][] = array_fill_keys(array_keys(EvaluationForm::EVIDENCE_FIELDS), '');
        $this->post(route('teacher-evaluations.store'), $data)->assertSessionHasNoErrors();
        $record = TeacherEvaluation::firstOrFail();
        $this->assertNull($record->appraisal_year);
        $this->assertNull($record->score);
        $this->assertCount(2, $record->evidence);
        $this->assertCount(1, $record->development);
        $this->get(route('teacher-evaluations.show', $record))->assertOk()->assertSee('Open Day')->assertSee('Supported two colleagues');
        $this->post(route('teacher-evaluations.store'), $this->event($teacher, ['event_name' => 'Sports Day']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('teacher_evaluations', 2);
    }

    public function test_optional_event_rows_signatures_and_followup_details_warn_but_can_be_saved(): void
    {
        $teacher = Teacher::factory()->create();
        $this->actingAs($this->hr());
        foreach ([
            ['evidence' => []],
            ['evidence' => [['examples' => 'Some evidence']]],
            ['development' => [['goal' => 'Training']]],
            ['teacher_signed_name' => 'Signed Teacher'],
            ['follow_up_completed_date' => '2026-12-01'],
        ] as $missing) {
            $this->post(route('teacher-evaluations.store'), $this->event($teacher, $missing))->assertSessionHasNoErrors();
            $record = TeacherEvaluation::latest('id')->firstOrFail();
            $this->assertSame('completed', $record->status);
            $this->assertNotEmpty(EvaluationForm::missingDetails($record));
        }
        $this->assertDatabaseCount('teacher_evaluations', 5);
        $this->post(route('teacher-evaluations.store'), $this->event($teacher, ['evidence' => ['bad input']]))->assertSessionHasErrors('evidence.0');
        $this->post(route('teacher-evaluations.store'), $this->event($teacher, ['teacher_signed_date' => 'not-a-date']))->assertSessionHasErrors('teacher_signed_date');
    }

    public function test_version_checks_prevent_overwriting_other_edits_and_identity_cannot_change(): void
    {
        $teacher = Teacher::factory()->create();
        $other = Teacher::factory()->create();
        $this->actingAs($this->hr());
        $record = $this->createAnnual($teacher);
        $this->put(route('teacher-evaluations.update', $record), $this->annual($teacher, ['version' => 1, 'teacher_comments' => 'Saved comment']))->assertSessionHasNoErrors();
        $this->put(route('teacher-evaluations.update', $record), $this->annual($teacher, ['version' => 1, 'teacher_comments' => 'Stale comment']))->assertSessionHasErrors('version');
        $this->assertSame('Saved comment', $record->fresh()->teacher_comments);
        $this->put(route('teacher-evaluations.update', $record), $this->annual($other, ['version' => 2]))->assertSessionHasErrors('teacher_id');
        $this->put(route('teacher-evaluations.update', $record), $this->event($teacher, ['version' => 2]))->assertSessionHasErrors('type');
        $this->assertDatabaseHas('audit_logs', ['action' => 'teacher_evaluation.updated']);
    }

    public function test_filters_use_appraisal_year_event_dates_status_and_followup(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 20));
        $this->actingAs($this->hr());
        $teacher = Teacher::factory()->create();
        $annual = $this->createAnnual($teacher);
        $this->post(route('teacher-evaluations.store'), $this->event($teacher, ['development' => [['goal' => 'Goal', 'training' => 'Training', 'measure' => 'Measure', 'target_date' => '2026-10-01']]]))->assertSessionHasNoErrors();
        $event = TeacherEvaluation::latest('id')->firstOrFail();
        $this->get(route('teacher-evaluations.index', ['year' => 2026]))->assertOk()->assertViewHas('evaluations', fn ($e) => $e->modelKeys() === [$annual->id]);
        $this->get(route('teacher-evaluations.index', ['event_from' => '2026-10-15', 'event_to' => '2026-10-15']))->assertOk()->assertViewHas('evaluations', fn ($e) => $e->modelKeys() === [$event->id]);
        $this->get(route('teacher-evaluations.index', ['event_to' => '2026-10-15']))->assertOk()->assertViewHas('evaluations', fn ($e) => $e->modelKeys() === [$event->id]);
        $this->get(route('teacher-evaluations.index', ['q' => 'Open Day', 'section' => 'Secondary', 'status' => 'completed']))->assertOk()->assertViewHas('evaluations', fn ($e) => $e->modelKeys() === [$event->id]);
        $this->get(route('teacher-evaluations.index', ['follow_up' => 'overdue']))->assertOk()->assertViewHas('evaluations', fn ($e) => $e->modelKeys() === [$event->id]);
        $this->get(route('teacher-evaluations.index', ['event_from' => '2026-12-01', 'event_to' => '2026-01-01']))->assertSessionHasErrors('event_to');
        $event->update(['follow_up_completed_date' => '2026-10-20', 'follow_up_notes' => 'Reviewed with teacher']);
        $this->get(route('teacher-evaluations.index', ['follow_up' => 'overdue']))->assertOk()->assertViewHas('evaluations', fn ($e) => $e->isEmpty());
        $this->get(route('teacher-evaluations.index', ['follow_up' => 'completed']))->assertOk()->assertViewHas('evaluations', fn ($e) => $e->modelKeys() === [$event->id]);
    }

    public static function templates(): array
    {
        return [['annual', 'pdf'], ['annual', 'xlsx'], ['event', 'pdf'], ['event', 'xlsx']];
    }

    #[DataProvider('templates')]
    public function test_blank_printable_templates_download(string $type, string $format): void
    {
        $response = $this->actingAs($this->hr())->get(route('teacher-evaluations.template', compact('type', 'format')))->assertOk()->assertDownload('teacher-evaluation-'.$type.'-template.'.$format);
        $bytes = $format === 'xlsx' ? $response->streamedContent() : $response->getContent();
        $this->assertStringStartsWith($format === 'xlsx' ? 'PK' : '%PDF-', $bytes);
        $this->preview($type.'-template.'.$format, $bytes);
        if ($format === 'xlsx') {
            $book = $this->readWorkbook($bytes);
            $this->assertNotNull($book->getSheetByName('Instructions'));
            if ($type === 'annual') {
                $sheet = $book->getSheetByName('Annual');
                $this->assertSame('', $sheet->getCell('G25')->getCalculatedValue());
                $this->assertSame('Ratings incomplete', $sheet->getCell('H25')->getCalculatedValue());
                for ($i = 12; $i <= 24; $i++) {
                    $sheet->setCellValue('F'.$i, 5);
                }
                $book->getCalculationEngine()->clearCalculationCache();
                $this->assertEquals(100, $sheet->getCell('G25')->getCalculatedValue());
                $this->assertSame('Outstanding', $sheet->getCell('H25')->getCalculatedValue());
                $sheet->setCellValue('F12', null);
                $book->getCalculationEngine()->clearCalculationCache();
                $this->assertSame('', $sheet->getCell('G25')->getCalculatedValue());
            }
            $book->disconnectWorksheets();
        }
    }

    public function test_filled_exports_and_filters_contain_only_requested_records_and_safe_excel_text(): void
    {
        $this->actingAs($this->hr());
        $teacher = Teacher::factory()->create(['name' => '=HYPERLINK("bad", "Teacher")']);
        $annual = $this->createAnnual($teacher, ['teacher_comments' => '=2+2']);
        $this->post(route('teacher-evaluations.store'), $this->event($teacher))->assertSessionHasNoErrors();
        $event = TeacherEvaluation::latest('id')->firstOrFail();
        foreach ([$annual, $event] as $evaluation) {
            foreach (['pdf', 'xlsx'] as $format) {
                $response = $this->get(route('teacher-evaluations.download', ['evaluation' => $evaluation, 'format' => $format]))->assertOk();
                $bytes = $format === 'xlsx' ? $response->streamedContent() : $response->getContent();
                $this->assertStringStartsWith($format === 'xlsx' ? 'PK' : '%PDF-', $bytes);
                $this->preview($evaluation->type.'-filled.'.$format, $bytes);
            }
        }
        $response = $this->get(route('teacher-evaluations.export', ['format' => 'xlsx', 'year' => 2026]))->assertOk();
        $book = $this->readWorkbook($response->streamedContent());
        $this->assertNotNull($book->getSheetByName('Annual '.$annual->id));
        $this->assertNull($book->getSheetByName('Event review '.$event->id));
        $this->assertSame(DataType::TYPE_STRING, $book->getSheet(0)->getCell('A4')->getDataType());
        $this->assertEquals(80, $book->getSheet(0)->getCell('G25')->getCalculatedValue());
        $this->assertSame('Exceeds expectations', $book->getSheet(0)->getCell('H25')->getCalculatedValue());
        $book->disconnectWorksheets();
        $response = $this->get(route('teacher-evaluations.export', ['format' => 'xlsx']))->assertOk();
        $book = $this->readWorkbook($response->streamedContent());
        $this->assertSame(DataType::TYPE_STRING, $book->getSheetByName('Report list')->getCell('B6')->getDataType());
        $this->assertCount(5, $book->getAllSheets());
        $book->disconnectWorksheets();
    }

    public function test_view_manage_export_delete_permissions_are_separate_and_admin_can_do_everything(): void
    {
        $this->actingAs($this->hr());
        $record = $this->createAnnual();
        $this->delete(route('teacher-evaluations.destroy', $record))->assertForbidden();
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('teacher_evaluations.view');
        $this->actingAs($viewer)->get(route('teacher-evaluations.show', $record))->assertOk()->assertDontSee('Edit evaluation')->assertDontSee('Download Excel');
        $this->get(route('teacher-evaluations.create'))->assertForbidden();
        $this->get(route('teacher-evaluations.download', ['evaluation' => $record, 'format' => 'pdf']))->assertForbidden();
        $this->get(route('teacher-evaluations.template', ['type' => 'annual', 'format' => 'xlsx']))->assertForbidden();
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $this->actingAs($admin)->get(route('users.create'))->assertOk()->assertSee('HR admin');
        $this->get(route('teacher-evaluations.edit', $record))->assertOk();
        $this->delete(route('teachers.destroy', $record->teacher_id))->assertSessionHasErrors('teacher');
        $this->delete(route('teacher-evaluations.destroy', $record))->assertRedirect(route('teacher-evaluations.index'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'teacher_evaluation.deleted']);
        $this->assertDatabaseMissing('teacher_evaluations', ['id' => $record->id]);
    }

    public function test_empty_filtered_exports_and_export_limit(): void
    {
        $this->actingAs($this->hr());
        $response = $this->get(route('teacher-evaluations.export', ['format' => 'pdf', 'year' => 2024]))->assertOk();
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $book = $this->readWorkbook($this->get(route('teacher-evaluations.export', ['format' => 'xlsx', 'year' => 2024]))->assertOk()->streamedContent());
        $this->assertStringContainsString('No evaluations match', $book->getSheet(0)->getCell('A6')->getValue());
        $book->disconnectWorksheets();
        $teacher = Teacher::factory()->create();
        for ($i = 0; $i < 101; $i++) {
            TeacherEvaluation::create(['teacher_id' => $teacher->id, 'teacher_name' => $teacher->name, 'type' => 'event', 'event_name' => 'Event '.$i]);
        }
        $this->get(route('teacher-evaluations.export', ['format' => 'pdf']))->assertSessionHasErrors('export');
        $this->get(route('teacher-evaluations.export', ['format' => 'xlsx']))->assertSessionHasErrors('export');
    }

    public function test_saved_excel_score_bands_follow_rating_changes_and_incomplete_values(): void
    {
        $record = new TeacherEvaluation(['type' => 'annual', 'criteria' => EvaluationForm::criteria()]);
        $book = app(EvaluationExport::class)->workbook(collect([$record]), true);
        $sheet = $book->getSheetByName('Annual');
        foreach ([5 => [100, 'Outstanding'], 4 => [80, 'Exceeds expectations'], 3 => [60, 'Needs improvement'], 2 => [40, 'Unsatisfactory'], 1 => [20, 'Unsatisfactory']] as $rating => [$score, $band]) {
            for ($i = 12; $i <= 24; $i++) {
                $sheet->setCellValue('F'.$i, $rating);
            }
            $book->getCalculationEngine()->clearCalculationCache();
            $this->assertEquals($score, $sheet->getCell('G25')->getCalculatedValue());
            $this->assertSame($band, $sheet->getCell('H25')->getCalculatedValue());
            $this->assertSame($band, EvaluationForm::band($score));
        }
        foreach ([3 => [[1, 2, 10], 70, 'Meets expectations well'], 4 => [[1, 2, 3, 4, 5], 90, 'Outstanding']] as $base => [$upgraded, $score, $band]) {
            for ($i = 12; $i <= 24; $i++) {
                $sheet->setCellValue('F'.$i, in_array($i - 11, $upgraded) ? 5 : $base);
            }
            $book->getCalculationEngine()->clearCalculationCache();
            $this->assertEquals($score, $sheet->getCell('G25')->getCalculatedValue());
            $this->assertSame($band, $sheet->getCell('H25')->getCalculatedValue());
        }
        $sheet->setCellValue('F12', null);
        $book->getCalculationEngine()->clearCalculationCache();
        $this->assertSame('', $sheet->getCell('G25')->getCalculatedValue());
        $this->assertSame('Ratings incomplete', $sheet->getCell('H25')->getCalculatedValue());
        $book->disconnectWorksheets();
    }

    public function test_long_narrative_is_preserved_in_print_exports(): void
    {
        $this->actingAs($this->hr());
        $notes = str_repeat('Observed careful preparation and clear communication with the team. ', 60).'FINAL EVIDENCE MARKER';
        $record = $this->createAnnual(null, ['remarks' => [1 => $notes], 'teacher_comments' => str_repeat('The teacher discussed the development goals. ', 100).'FINAL COMMENT MARKER']);
        foreach (['pdf', 'xlsx'] as $format) {
            $response = $this->get(route('teacher-evaluations.download', ['evaluation' => $record, 'format' => $format]))->assertOk();
            $bytes = $format === 'xlsx' ? $response->streamedContent() : $response->getContent();
            $this->assertStringStartsWith($format === 'xlsx' ? 'PK' : '%PDF-', $bytes);
            $this->preview('annual-long.'.$format, $bytes);
            if ($format === 'xlsx') {
                $book = $this->readWorkbook($bytes);
                $joined = '';
                foreach ($book->getAllSheets() as $sheet) {
                    foreach ($sheet->toArray() as $row) {
                        $joined .= implode(' ', array_filter($row, fn ($v) => is_scalar($v)));
                    }
                }
                $this->assertStringContainsString('FINAL EVIDENCE MARKER', $joined);
                $this->assertStringContainsString('FINAL COMMENT MARKER', $joined);
                $book->disconnectWorksheets();
            }
        }
    }

    public function test_supervisor_scores_average_only_completed_columns_and_optional_columns_remain_blank(): void
    {
        $this->actingAs($this->hr());
        $teacher = Teacher::factory()->create();
        $record = $this->createAnnual($teacher, [
            'additional_ratings' => [2 => array_fill(1, 13, 5)],
            'supervisors' => ['Principal', 'Deputy Principal', null],
        ]);
        $this->assertSame(90.0, $record->score);
        $this->assertSame([80.0, 100.0, null], EvaluationForm::supervisorScores($record->criteria));
        $this->assertSame(['Principal', 'Deputy Principal', null], $record->supervisors);
        $this->assertSame([4, 5, null], $record->criteria[0]['supervisor_ratings']);
        $this->get(route('teacher-evaluations.show', $record))->assertOk()->assertSee('Supervisor 3')->assertSee('90.00 / 100');
        $this->put(route('teacher-evaluations.update', $record), $this->annual($teacher, [
            'version' => 1, 'additional_ratings' => [2 => array_fill(1, 13, 5), 3 => array_fill(1, 13, 3)],
        ]))->assertSessionHasNoErrors();
        $this->assertSame(80.0, $record->fresh()->score);
        $this->get(route('teacher-evaluations.edit', $record))->assertOk()->assertSee('name="additional_ratings[3][13]"', false);

        // A completed second supervisor is valid even when Supervisor 1 is unused.
        $onlySecond = $this->createAnnual(null, ['ratings' => [], 'additional_ratings' => [2 => array_fill(1, 13, 3)]]);
        $this->assertSame(60.0, $onlySecond->score);
        $this->assertSame([null, 60.0, null], EvaluationForm::supervisorScores($onlySecond->criteria));
    }

    public function test_top_score_summary_shows_partial_points_without_treating_them_as_a_final_score(): void
    {
        $this->actingAs($this->hr());
        $record = $this->createAnnual(null, ['ratings' => [1 => 4, 2 => 4, 3 => 5, 4 => 3, 5 => 4]]);
        $summary = EvaluationForm::scoreSummary($record->criteria);
        $this->assertNull($record->score);
        $this->assertNull($summary['score']);
        $this->assertSame(41.0, $summary['entered_score']);
        $this->assertSame(['rated' => 5, 'total' => 13, 'points' => 41.0, 'complete' => false], $summary['supervisors'][0]);
        $this->assertNull($summary['supervisors'][1]['points']);
        $this->get(route('teacher-evaluations.show', $record))->assertOk()
            ->assertSeeInOrder(['Score entered so far', '41.00', '/ 100', '5 of 13 ratings', 'Download PDF', 'Annual Teacher Performance Appraisal'])
            ->assertSee('Missing ratings are still pending; the final score has not been calculated.');

        // A completed review determines the overall score even with a partial second review.
        $mixed = $this->createAnnual(null, ['additional_ratings' => [2 => [1 => 5]]]);
        $mixedSummary = EvaluationForm::scoreSummary($mixed->criteria);
        $this->assertSame(80.0, $mixedSummary['score']);
        $this->assertSame(1, $mixedSummary['completed']);
        $this->assertSame(10.0, $mixedSummary['supervisors'][1]['points']);
        $this->get(route('teacher-evaluations.show', $mixed))->assertOk()
            ->assertSeeInOrder(['Overall score', '80.00', '/ 100', 'Average of 1 completed supervisor review.'])
            ->assertDontSee('Score entered so far');

        $empty = $this->createAnnual(null, ['ratings' => []]);
        $this->assertNull(EvaluationForm::scoreSummary($empty->criteria)['entered_score']);
        $this->get(route('teacher-evaluations.show', $empty))->assertOk()->assertSee('No ratings entered yet.');
        $this->assertNull(EvaluationForm::scoreSummary([])['score']);

        $this->post(route('teacher-evaluations.store'), $this->event(Teacher::factory()->create()))->assertSessionHasNoErrors();
        $event = TeacherEvaluation::latest('id')->firstOrFail();
        $this->get(route('teacher-evaluations.show', $event))->assertOk()->assertDontSee('id="evaluation-score-title"', false);
    }

    public function test_partial_supervisor_columns_warn_and_do_not_change_the_average(): void
    {
        $this->actingAs($this->hr());
        $teacher = Teacher::factory()->create();
        $payload = $this->annual($teacher, ['additional_ratings' => [2 => [1 => 5]]]);
        $this->post(route('teacher-evaluations.store'), array_replace($payload, ['acknowledge_incomplete' => false]))->assertSessionHasNoErrors()->assertSessionHas('evaluation_missing_details');
        $this->assertDatabaseCount('teacher_evaluations', 0);
        $record = $this->createAnnual($teacher, array_replace($payload, ['status' => 'draft']));
        $this->assertSame(80.0, $record->score);
        $this->assertSame([80.0, null, null], EvaluationForm::supervisorScores($record->criteria));
        $this->put(route('teacher-evaluations.update', $record), array_replace($payload, ['version' => 1, 'additional_ratings' => [2 => []]]))->assertSessionHasNoErrors();
        $this->assertSame('completed', $record->fresh()->status);
        foreach ([6, 2.5, 'invalid'] as $invalid) {
            $this->post(route('teacher-evaluations.store'), $this->annual(Teacher::factory()->create(), ['additional_ratings' => [3 => [1 => $invalid]]]))->assertSessionHasErrors('additional_ratings.3.1');
        }
    }

    public function test_pdf_has_the_same_score_summary_before_the_annual_ratings(): void
    {
        $this->actingAs($this->hr());
        foreach (['partial' => [1 => 4, 2 => 4, 3 => 5, 4 => 3, 5 => 4], 'complete' => array_fill(1, 13, 4), 'empty' => []] as $state => $ratings) {
            $record = $this->createAnnual(null, ['ratings' => $ratings]);
            $data = ['evaluations' => collect([$record]), 'blank' => false, 'filters' => []];
            $html = view('teacher-evaluations.pdf', $data)->render();
            $scoreText = match ($state) {
                'partial' => '41.00 / 100',
                'complete' => '80.00 / 100',
                default => 'Not entered / 100',
            };
            $this->assertStringContainsString($scoreText, $html);
            $this->assertLessThan(strpos($html, '<strong>Rating scale:'), strpos($html, 'class="pdf-score-summary"'));
            if ($state === 'partial') {
                $this->assertStringContainsString('5 of 13 ratings - Incomplete', $html);
                $this->assertStringContainsString('Missing ratings are pending; the final score has not been calculated.', $html);
            } elseif ($state === 'complete') {
                $this->assertStringContainsString('Average of 1 completed supervisor review(s). Partial reviews are excluded.', $html);
            } else {
                $this->assertStringContainsString('No ratings entered yet.', $html);
            }
            $response = $this->get(route('teacher-evaluations.download', ['evaluation' => $record, 'format' => 'pdf']))->assertOk();
            $this->assertStringStartsWith('%PDF', $response->getContent());
            $this->preview('annual-score-'.$state.'.pdf', $response->getContent());

            $data['blank'] = true;
            $this->assertStringNotContainsString('class="pdf-score-summary"', view('teacher-evaluations.pdf', $data)->render());
        }
        $event = new TeacherEvaluation(['type' => 'event', 'status' => 'draft']);
        $this->assertStringNotContainsString('class="pdf-score-summary"', view('teacher-evaluations.pdf', ['evaluations' => collect([$event]), 'blank' => false, 'filters' => []])->render());
    }

    public function test_legacy_single_supervisor_appraisals_keep_their_scores_and_edit_values(): void
    {
        $this->actingAs($this->hr());
        $record = $this->createAnnual();
        $legacy = array_map(function ($criterion) {
            unset($criterion['supervisor_ratings']);

            return $criterion;
        }, $record->criteria);
        $record->update(['criteria' => $legacy, 'supervisors' => null]);
        $this->assertSame([80.0, null, null], EvaluationForm::supervisorScores($record->fresh()->criteria));
        $this->get(route('teacher-evaluations.edit', $record))->assertOk()->assertSee('Supervisor 1 rating');
        $book = app(EvaluationExport::class)->workbook(collect([$record->fresh()]));
        $sheet = $book->getSheet(0);
        $this->assertEquals(4, $sheet->getCell('F12')->getValue());
        $this->assertNull($sheet->getCell('H12')->getValue());
        $this->assertEquals(80, $sheet->getCell('G25')->getCalculatedValue());
        $book->disconnectWorksheets();
    }

    public function test_excel_supervisor_formulas_match_server_averages_and_ignore_partial_reviews(): void
    {
        $this->actingAs($this->hr());
        $record = $this->createAnnual(null, ['additional_ratings' => [2 => array_fill(1, 13, 5)]]);
        $book = app(EvaluationExport::class)->workbook(collect([$record]));
        $sheet = $book->getSheet(0);
        $this->assertEquals(90, $sheet->getCell('G25')->getCalculatedValue());
        $this->assertEquals(80, $sheet->getCell('G26')->getCalculatedValue());
        $this->assertEquals(100, $sheet->getCell('I26')->getCalculatedValue());
        $this->assertSame('', $sheet->getCell('K26')->getCalculatedValue());
        $sheet->setCellValue('J12', 1);
        $book->getCalculationEngine()->clearCalculationCache();
        $this->assertEquals(90, $sheet->getCell('G25')->getCalculatedValue());
        for ($row = 12; $row <= 24; $row++) {
            $sheet->setCellValue('J'.$row, 3);
        }
        $book->getCalculationEngine()->clearCalculationCache();
        $this->assertEquals(80, $sheet->getCell('G25')->getCalculatedValue());
        $this->assertSame('Exceeds expectations', $sheet->getCell('H25')->getCalculatedValue());
        $this->assertSame('whole', $sheet->getCell('J12')->getDataValidation()->getType());
        $this->assertSame('A1:L'.$sheet->getHighestRow(), $sheet->getPageSetup()->getPrintArea());
        $book->disconnectWorksheets();
        $response = $this->get(route('teacher-evaluations.download', ['evaluation' => $record, 'format' => 'pdf']))->assertOk();
        $this->preview('annual-supervisors.pdf', $response->getContent());
        $response = $this->get(route('teacher-evaluations.download', ['evaluation' => $record, 'format' => 'xlsx']))->assertOk();
        $this->preview('annual-supervisors.xlsx', $response->streamedContent());
    }

    public function test_profile_actions_history_and_personalised_templates_are_teacher_specific(): void
    {
        $this->actingAs($this->hr());
        $teacher = Teacher::factory()->create(['name' => 'Profile Teacher', 'assigned_classes' => 'Grade 3', 'salary_amount' => 987654]);
        $record = $this->createAnnual($teacher);
        $this->post(route('teacher-evaluations.store'), $this->event(Teacher::factory()->create(), ['event_name' => 'Different teacher event']))->assertSessionHasNoErrors();
        $this->get(route('hr.teachers.show', $teacher))->assertOk()->assertSee('2026 Annual appraisal')->assertDontSee('Different teacher event')->assertSee(route('teacher-evaluations.create', ['teacher_id' => $teacher->id, 'type' => 'annual']))->assertViewHas('history', fn ($history) => $history->modelKeys() === [$record->id]);
        $this->get(route('teacher-evaluations.create', ['type' => 'event', 'teacher_id' => $teacher->id]))->assertOk()->assertViewHas('evaluation', fn ($evaluation) => $evaluation->teacher_id == $teacher->id && $evaluation->subject_class === 'Grade 3');
        $response = $this->get(route('teacher-evaluations.template', ['type' => 'annual', 'format' => 'xlsx', 'teacher_id' => $teacher->id]))->assertOk();
        $book = $this->readWorkbook($response->streamedContent());
        $sheet = $book->getSheetByName('Annual');
        $this->assertSame('Teacher: Profile Teacher', $sheet->getCell('A4')->getValue());
        $this->assertSame('Subject / class: Grade 3', $sheet->getCell('G5')->getValue());
        $book->disconnectWorksheets();
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('hr.directory.view');
        $this->actingAs($viewer)->get(route('hr.teachers.show', $teacher))->assertOk()->assertDontSee('2026 Annual appraisal')->assertDontSee('New annual appraisal')->assertDontSee('987,654');
    }

    public function test_admin_sidebar_order_is_shared_and_hr_only_sees_authorised_groups(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $response = $this->actingAs($admin)->get(route('hr.teachers.index'))->assertOk();
        $response->assertSeeInOrder(['<span>Dashboard</span>', '<span>Opening Balance</span>', '<span>Owner Bank Transfer</span>', '<span>Revenue</span>', 'Exam &amp; Facility Fees', '<span>Expense</span>', 'Students</span>', 'Teachers</span>', 'Evaluations</span>', '<span>Class Rooms</span>'], false);
        $response->assertSee('Student Details')->assertSee('Teacher Details')->assertSee('Evaluation reports');
        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, '<span>Owner Bank Transfer</span>'));
        $this->assertSame(2, substr_count($html, 'aria-label="Toggle Teachers menu"'));
        $this->actingAs($this->hr())->get(route('hr.teachers.index'))->assertOk()->assertSee('Student Details')->assertSee('Teacher Details')->assertSee('Add annual appraisal')->assertDontSee('Owner Bank Transfer')->assertDontSee('Exam &amp; Facility Fees', false)->assertDontSee('Salary Payments');
    }

    public function test_tabular_event_exports_keep_long_entries_dates_and_literal_text(): void
    {
        $this->actingAs($this->hr());
        $teacher = Teacher::factory()->create();
        $evidence = str_repeat('Observed careful preparation and communication with the team. ', 60).'FINAL TABLE EVIDENCE';
        $notes = str_repeat('The teacher will review classroom activities with the coordinator. ', 55).'FINAL TABLE DEVELOPMENT';
        $data = $this->event($teacher, [
            'evidence' => [['area' => 'School events', 'examples' => $evidence, 'reviewer' => '=1+2', 'review_date' => '2026-11-01']],
            'development' => [['goal' => 'Delegate tasks', 'training' => 'Mentoring', 'measure' => 'Two delegated tasks', 'target_date' => '2026-12-01', 'notes' => $notes]],
        ]);
        $this->post(route('teacher-evaluations.store'), $data)->assertSessionHasNoErrors();
        $record = TeacherEvaluation::latest('id')->firstOrFail();
        $this->get(route('teacher-evaluations.show', $record))->assertOk()->assertSee('FINAL TABLE EVIDENCE')->assertSee('FINAL TABLE DEVELOPMENT');
        $book = app(EvaluationExport::class)->workbook(collect([$record]));
        $sheet = $book->getSheet(0);
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('H14')->getDataType());
        $this->assertSame('=1+2', $sheet->getCell('H14')->getValue());
        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('G14')->getDataType());
        $this->assertSame('dd mmm yyyy', $sheet->getStyle('G14')->getNumberFormat()->getFormatCode());
        $joined = implode(' ', array_map(fn ($row) => implode(' ', array_filter($row, 'is_scalar')), $sheet->toArray()));
        $this->assertStringContainsString('FINAL TABLE EVIDENCE', $joined);
        $this->assertStringContainsString('FINAL TABLE DEVELOPMENT', $joined);
        $book->disconnectWorksheets();
        foreach (['pdf', 'xlsx'] as $format) {
            $response = $this->get(route('teacher-evaluations.download', ['evaluation' => $record, 'format' => $format]))->assertOk();
            $this->preview('event-long.'.$format, $format === 'pdf' ? $response->getContent() : $response->streamedContent());
        }
        $rows = EvaluationForm::tableRows([['examples' => $evidence]], ['examples' => 'Evidence']);
        $this->assertGreaterThan(1, count($rows));
        $this->assertTrue($rows[1]['continued']);
        $this->assertSame(preg_replace('/\s+/u', ' ', $evidence), implode(' ', array_column(array_column($rows, 'cells'), 'examples')));
    }

    public function test_missing_details_require_warning_acknowledgement_without_losing_input(): void
    {
        $this->actingAs($this->hr());
        $teacher = Teacher::factory()->create();
        $payload = $this->annual($teacher, ['ratings' => array_fill(1, 5, 4), 'acknowledge_incomplete' => false, 'evaluator' => null, 'period_start' => null, 'period_end' => '2026-12-31']);
        $form = route('teacher-evaluations.create', ['type' => 'annual', 'teacher_id' => $teacher->id]);
        $this->from($form)->post(route('teacher-evaluations.store'), $payload)->assertRedirect($form)->assertSessionHasNoErrors()->assertSessionHas('evaluation_pending_status', 'completed')->assertSessionHas('evaluation_missing_details', fn ($fields) => in_array('Supervisor 1: Student care and development rating', $fields) && in_array('Evaluator / authorised reviewer', $fields) && ! collect($fields)->contains(fn ($field) => str_starts_with($field, 'Supervisor 2:')))->assertSessionHasInput('ratings.5', 4);
        $this->assertDatabaseCount('teacher_evaluations', 0);
        $this->get($form)->assertOk()->assertSee('Some details are incomplete')->assertSee('Save anyway')->assertSee('Return to form');
        $this->post(route('teacher-evaluations.store'), array_replace($payload, ['acknowledge_incomplete' => true]))->assertSessionHasNoErrors()->assertRedirect();
        $record = TeacherEvaluation::firstOrFail();
        $this->assertSame('completed', $record->status);
        $this->assertNull($record->score);
        $this->assertNull($record->period_start);
        $this->assertSame('2026-12-31', $record->period_end->format('Y-m-d'));
        $this->assertSame(4, $record->criteria[4]['rating']);
        $this->get(route('teacher-evaluations.show', $record))->assertOk()->assertSee('Some review details have not been entered');
    }

    public function test_incomplete_update_warning_does_not_write_until_confirmed_and_keeps_the_requested_status(): void
    {
        $this->actingAs($this->hr());
        $teacher = Teacher::factory()->create();
        $record = $this->createAnnual($teacher);
        $payload = $this->annual($teacher, ['version' => 1, 'ratings' => [], 'status' => 'draft', 'acknowledge_incomplete' => false]);
        $url = route('teacher-evaluations.edit', $record);
        $this->from($url)->put(route('teacher-evaluations.update', $record), $payload)->assertRedirect($url)->assertSessionHas('evaluation_missing_details', ['Annual performance ratings (no ratings entered)'])->assertSessionHas('evaluation_pending_status', 'draft');
        $this->assertSame(1, $record->fresh()->version);
        $this->assertSame(80.0, $record->fresh()->score);
        $this->assertSame('completed', $record->fresh()->status);
        $this->put(route('teacher-evaluations.update', $record), array_replace($payload, ['acknowledge_incomplete' => true]))->assertSessionHasNoErrors();
        $this->assertSame('draft', $record->fresh()->status);
        $this->assertNull($record->fresh()->score);
        $this->assertSame(2, $record->fresh()->version);
        $this->post(route('teacher-evaluations.store'), $this->annual(Teacher::factory()->create(), ['teacher_id' => null]))->assertSessionHasErrors('teacher_id');
        $this->post(route('teacher-evaluations.store'), $this->annual(Teacher::factory()->create(), ['appraisal_year' => null]))->assertSessionHasErrors('appraisal_year');
        $this->post(route('teacher-evaluations.store'), $this->event($teacher, ['event_name' => null]))->assertSessionHasErrors('event_name');
    }

    private function readWorkbook(string $bytes): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $path = tempnam(sys_get_temp_dir(), 'evaluation-xlsx-');
        try {
            file_put_contents($path, $bytes);

            return IOFactory::load($path);
        } finally {
            unlink($path);
        }
    }

    private function preview(string $name, string $bytes): void
    {
        if ($dir = getenv('TEACHER_EVALUATION_PREVIEW_DIR')) {
            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($dir.'/'.$name, $bytes);
        }
    }
}
