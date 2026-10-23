<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\Student;
use App\Models\StudentPromotionHistory;
use App\Models\User;
use App\Services\Billing\ClassFeeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClassFeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->forgetInstance('settings');
        $this->travelTo(Carbon::parse('2026-10-20 12:00:00'));
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin', 'web'));
        $this->actingAs($admin);
    }

    private function schoolClass(array $settings = []): ClassRoom
    {
        $class = ClassRoom::create(['name' => 'Grade '.Str::random(6), 'active' => true]);
        app(ClassFeeService::class)->configure($class, array_replace([
            'exam_fee' => '1000', 'exam_fee_start_date' => '2026-04-20',
            'facility_fee' => '2000', 'facility_fee_start_date' => '2026-01-15',
        ], $settings));

        return $class;
    }

    private function student(ClassRoom $class, array $values = []): Student
    {
        return Student::create(array_replace([
            'name' => 'Test Student '.Str::random(4), 'admission_number' => Str::random(8),
            'class_room_id' => $class->id, 'joining_date' => '2026-01-01',
            'active' => true, 'alumni' => false,
        ], $values));
    }

    private function payload(ClassRoom $class, Student $student, string $date = '2026-04-20', array $values = [], string $type = 'exam'): array
    {
        $schedule = $class->feeSchedules()->where('fee_type', $type)->whereNull('ends_on')->firstOrFail();

        return array_replace([
            'submission_id' => (string) Str::uuid(), 'student_id' => $student->id,
            'cycle' => $schedule->id.':'.$date, 'amount' => $schedule->amount,
            'paid_at' => '2026-10-20', 'payment_method' => 'cash',
        ], $values);
    }

    private function collect(ClassRoom $class, array $payload, string $type = 'exam')
    {
        return $this->post(route('class-fees.store', ['classroom' => $class, 'type' => $type]), $payload);
    }

    private function tracker(ClassRoom $class, string $type = 'exam', array $filters = [])
    {
        return $this->get(route('class-fees.index', $filters + ['type' => $type, 'class_room_id' => $class->id, 'year' => 2026, 'include_facility' => 0]));
    }

    public function test_class_form_saves_both_fees_and_their_independent_starting_dates(): void
    {
        $this->get(route('classrooms.create'))->assertOk()->assertSee('exam_fee_start_date', false)->assertSee('facility_fee_start_date', false);
        $this->post(route('classrooms.store'), [
            'name' => 'Preschool', 'monthly_fee' => '3000', 'active' => '1',
            'exam_fee' => '1500', 'exam_fee_start_date' => '2026-04-20',
            'facility_fee' => '2500', 'facility_fee_start_date' => '2026-01-15',
        ])->assertSessionHasNoErrors()->assertRedirect(route('classrooms.index'));
        $class = ClassRoom::where('name', 'Preschool')->firstOrFail();
        $this->assertDatabaseHas('class_fee_schedules', ['class_room_id' => $class->id, 'fee_type' => 'exam', 'amount' => 1500]);
        $this->assertDatabaseHas('class_fee_schedules', ['class_room_id' => $class->id, 'fee_type' => 'facility', 'amount' => 2500]);
        $this->get(route('classrooms.edit', $class))->assertOk()->assertSee('2026-04-20')->assertSee('2026-01-15');
        $this->assertSame(3000.0, (float) $class->monthly_fee);
    }

    public static function invalidSettings(): array
    {
        return [['exam_fee', '-1'], ['exam_fee', '1.001'], ['exam_fee_start_date', ''],
            ['facility_fee', '-1'], ['facility_fee_start_date', 'invalid']];
    }

    #[DataProvider('invalidSettings')]
    public function test_invalid_fee_settings_do_not_create_a_class(string $field, string $value): void
    {
        $this->post(route('classrooms.store'), array_replace([
            'name' => 'Invalid class', 'exam_fee' => '1000', 'exam_fee_start_date' => '2026-01-01',
            'facility_fee' => '2000', 'facility_fee_start_date' => '2026-01-01',
        ], [$field => $value]))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('class_rooms', 0);
        $this->assertDatabaseCount('class_fee_schedules', 0);
    }

    public function test_quarterly_and_annual_dates_are_anchored_without_month_end_drift(): void
    {
        $class = $this->schoolClass(['exam_fee_start_date' => '2026-01-31', 'facility_fee_start_date' => '2024-02-29']);
        $service = app(ClassFeeService::class);
        $exam = $service->cycles($class->feeSchedules->where('fee_type', 'exam'), Carbon::parse('2026-01-01'), Carbon::parse('2026-12-31'));
        $this->assertSame(['2026-01-31', '2026-04-30', '2026-07-31', '2026-10-31'], $exam->map(fn ($cycle) => $cycle['due']->toDateString())->values()->all());
        $annual = $service->cycles($class->feeSchedules->where('fee_type', 'facility'), Carbon::parse('2024-01-01'), Carbon::parse('2028-12-31'));
        $this->assertSame(['2024-02-29', '2025-02-28', '2026-02-28', '2027-02-28', '2028-02-29'], $annual->map(fn ($cycle) => $cycle['due']->toDateString())->values()->all());
    }

    public function test_two_of_three_exams_paid_and_unpaid_facility_are_visible(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $this->collect($class, $this->payload($class, $student))->assertSessionHasNoErrors();
        $this->collect($class, $this->payload($class, $student, '2026-07-20'))->assertSessionHasNoErrors();
        $this->tracker($class)->assertOk()->assertSee('2 of 3 paid')
            ->assertViewHas('tracker', fn ($tracker) => $tracker['paid'] === 2000.0 && $tracker['due'] === 1000.0);
        $this->tracker($class, 'facility', ['status' => 'unpaid'])->assertOk()->assertSee($student->name)->assertSee('0 of 1 paid')
            ->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 2000.0 && $tracker['unpaid_students'] === 1);
        $this->assertDatabaseCount('revenues', 2);
        $this->assertDatabaseCount('expenses', 0);
        $this->get(route('revenue.items.receipt', Revenue::first()))->assertOk()->assertSee('Term paid:')->assertSee('20-04-2026');
    }

    public function test_partial_payments_finish_the_same_installment_and_retry_is_idempotent(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $payload = $this->payload($class, $student, values: ['amount' => '400']);
        $this->collect($class, $payload)->assertSessionHasNoErrors();
        $this->collect($class, $payload)->assertSessionHasNoErrors();
        $this->tracker($class)->assertOk()->assertSee('Partial')->assertSee('0 of 3 paid');
        $this->collect($class, $this->payload($class, $student, values: ['amount' => '601']))->assertSessionHasErrors('amount');
        $this->collect($class, $this->payload($class, $student, values: ['amount' => '600']))->assertSessionHasNoErrors();
        $this->tracker($class)->assertOk()->assertSee('1 of 3 paid');
        $this->assertDatabaseCount('revenues', 2);
    }

    public function test_cheques_reserve_the_balance_but_only_passed_cheques_count_as_paid(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $payload = $this->payload($class, $student, values: ['payment_method' => 'cheque', 'cheque_date' => '2026-10-21', 'cheque_number' => '123', 'cheque_bank' => 'Test Bank']);
        $this->collect($class, $payload)->assertSessionHasNoErrors();
        $this->tracker($class)->assertOk()->assertSee('Pending cheque')->assertSee('0 of 3 paid');
        $this->collect($class, $this->payload($class, $student))->assertSessionHasErrors('amount');
        $this->travelTo(Carbon::parse('2026-10-21'));
        $this->post(route('revenue.items.cheque.passed', Revenue::first()))->assertSessionHasNoErrors();
        $this->tracker($class)->assertOk()->assertSee('1 of 3 paid');
    }

    public function test_returned_cheques_refunds_and_cancellations_restore_installment_dues(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $this->collect($class, $this->payload($class, $student, values: ['payment_method' => 'cheque', 'cheque_date' => '2026-10-21', 'cheque_number' => '123', 'cheque_bank' => 'Test Bank']))->assertSessionHasNoErrors();
        $this->post(route('revenue.items.cheque.returned', Revenue::first()))->assertSessionHasNoErrors();
        $this->collect($class, $this->payload($class, $student))->assertSessionHasNoErrors();
        $paid = Revenue::latest('id')->first();
        $this->post(route('revenue.items.refund', $paid), ['amount' => '400', 'reason' => 'Correction'])->assertSessionHasNoErrors();
        $this->tracker($class)->assertOk()->assertSee('Partial')->assertSee('0 of 3 paid')
            ->assertViewHas('tracker', fn ($tracker) => $tracker['paid'] === 600.0 && $tracker['due'] === 2400.0);
        $this->delete(route('revenue.items.destroy', $paid), ['cancel_reason' => 'Wrong installment'])->assertSessionHasNoErrors();
        $this->tracker($class)->assertOk()->assertViewHas('tracker', fn ($tracker) => $tracker['paid'] === 0.0 && $tracker['due'] === 3000.0);
    }

    public function test_new_students_skip_earlier_exam_terms_but_owe_the_facility_fee_for_their_joining_year(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class, ['joining_date' => '2026-08-01']);
        $this->tracker($class)->assertOk()->assertSee('Not applicable')->assertSee('0 of 1 paid')
            ->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 1000.0);
        $this->collect($class, $this->payload($class, $student))->assertSessionHasErrors('cycle');
        $this->tracker($class, 'facility')->assertOk()->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 2000.0);
    }

    public function test_facility_fees_apply_from_the_joining_year_for_every_admission_month(): void
    {
        $class = $this->schoolClass(['facility_fee_start_date' => '2025-01-15']);
        foreach (['2025-12-31', '2026-01-01', '2026-06-30', '2026-12-31', '2027-01-01'] as $joined) {
            $this->student($class, ['joining_date' => $joined]);
        }
        $this->tracker($class, 'facility')->assertOk()
            ->assertViewHas('tracker', function ($tracker) {
                $dues = $tracker['rows']->mapWithKeys(fn ($row) => [$row['student']->joining_date->toDateString() => $row['due']]);

                return $tracker['due'] === 8000.0 && $tracker['unpaid_students'] === 4
                    && $dues->get('2026-12-31') === 2000.0 && $dues->get('2027-01-01') === 0.0;
            });
        $this->tracker($class, 'facility', ['year' => 2025])->assertOk()
            ->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 2000.0 && $tracker['unpaid_students'] === 1);
        $this->travelTo(Carbon::parse('2027-10-20'));
        $this->tracker($class, 'facility', ['year' => 2027])->assertOk()
            ->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 10000.0 && $tracker['unpaid_students'] === 5);
    }

    public function test_facility_fee_uses_fallback_admission_dates_when_joining_date_is_missing(): void
    {
        $class = $this->schoolClass(['facility_fee_start_date' => '2025-01-15']);
        $this->student($class, ['joining_date' => null, 'fee_start_date' => '2026-09-01']);
        $this->student($class, ['joining_date' => null, 'fee_start_date' => null]);
        $this->tracker($class, 'facility')->assertOk()
            ->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 4000.0);
        $this->tracker($class, 'facility', ['year' => 2025])->assertOk()
            ->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 0.0);
    }

    public function test_late_joiners_can_pay_the_annual_facility_fee_through_individual_collection(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class, ['joining_date' => '2026-10-01']);
        $this->collect($class, $this->payload($class, $student, '2026-01-15', type: 'facility'), 'facility')
            ->assertSessionHasNoErrors();
        $this->tracker($class, 'facility')->assertOk()->assertSee('1 of 1 paid')
            ->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 0.0 && $tracker['paid'] === 2000.0);
    }

    public function test_facility_fee_is_charged_once_per_year_after_a_class_transfer(): void
    {
        $old = $this->schoolClass();
        $new = $this->schoolClass(['facility_fee_start_date' => '2026-09-15']);
        $student = $this->student($new, ['joining_date' => '2026-03-01']);
        $move = StudentPromotionHistory::create(['student_id' => $student->id, 'from_class_room_id' => $old->id, 'to_class_room_id' => $new->id, 'action' => 'transfer']);
        $move->forceFill(['created_at' => Carbon::parse('2026-08-01')])->save();
        $this->tracker($old, 'facility', ['include_former' => 1])->assertOk()->assertSee($student->name)
            ->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 2000.0);
        $this->tracker($new, 'facility')->assertOk()
            ->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 0.0);
        $this->collect($new, $this->payload($new, $student, '2026-09-15', type: 'facility'), 'facility')->assertSessionHasErrors('cycle');
        $this->collect($old, $this->payload($old, $student, '2026-01-15', type: 'facility'), 'facility')->assertSessionHasNoErrors();
        $this->travelTo(Carbon::parse('2027-10-20'));
        $this->tracker($old, 'facility', ['year' => 2027])->assertOk()
            ->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 0.0);
        $this->tracker($new, 'facility', ['year' => 2027])->assertOk()
            ->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 2000.0);
    }

    public function test_promoted_students_keep_the_old_class_dues_and_are_not_double_charged(): void
    {
        $old = $this->schoolClass();
        $new = $this->schoolClass();
        $student = $this->student($new);
        $move = StudentPromotionHistory::create(['student_id' => $student->id, 'from_class_room_id' => $old->id, 'to_class_room_id' => $new->id, 'action' => 'promote']);
        $move->forceFill(['created_at' => Carbon::parse('2026-08-01')])->save();
        $this->tracker($old, filters: ['include_former' => 1])->assertOk()->assertSee($student->name)->assertSee('0 of 2 paid')
            ->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 2000.0);
        $this->tracker($new)->assertOk()->assertSee('0 of 1 paid')
            ->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 1000.0);
        $this->collect($old, $this->payload($old, $student))->assertSessionHasNoErrors();
        $this->collect($new, $this->payload($new, $student))->assertSessionHasErrors('cycle');
    }

    public function test_future_installments_can_be_prepaid_without_becoming_overdue(): void
    {
        $class = $this->schoolClass(['exam_fee_start_date' => '2026-01-31']);
        $student = $this->student($class);
        $this->tracker($class)->assertOk()->assertSee('Upcoming')->assertSee('0 of 3 paid');
        $this->collect($class, $this->payload($class, $student, '2026-10-31'))->assertSessionHasNoErrors();
        $this->tracker($class)->assertOk()->assertSee('0 of 3 paid')
            ->assertViewHas('tracker', fn ($tracker) => $tracker['paid'] === 1000.0 && $tracker['due'] === 3000.0);
    }

    public function test_fee_changes_keep_old_amounts_and_reject_retroactive_schedule_changes(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $this->collect($class, $this->payload($class, $student))->assertSessionHasNoErrors();
        $this->put(route('classrooms.update', $class), ['name' => 'Renamed', 'exam_fee' => '1500', 'exam_fee_start_date' => '2026-04-20'])
            ->assertSessionHasErrors('exam_fee_start_date');
        $this->assertNotSame('Renamed', $class->fresh()->name);
        $this->put(route('classrooms.update', $class), ['name' => $class->name, 'active' => 1, 'exam_fee' => '1500', 'exam_fee_start_date' => '2027-01-20'])
            ->assertSessionHasNoErrors();
        $this->tracker($class)->assertOk()->assertViewHas('tracker', fn ($tracker) => $tracker['paid'] === 1000.0 && $tracker['due'] === 2000.0);
        $this->tracker($class, filters: ['year' => 2027])->assertOk()
            ->assertViewHas('tracker', fn ($tracker) => $tracker['cycles']->every(fn ($cycle) => $cycle['amount'] === 1500.0));
    }

    public function test_existing_receipt_can_be_assigned_without_creating_duplicate_income(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $category = RevenueCategory::where('class_fee_type', 'exam')->firstOrFail();
        $receipt = Revenue::create(['bill_no' => 'OLD-1', 'student_id' => $student->id, 'revenue_category_id' => $category->id, 'amount' => 1000, 'paid_at' => '2026-06-01', 'payment_status' => 'confirmed']);
        $this->collect($class, $this->payload($class, $student, values: ['existing_revenue_id' => $receipt->id]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('revenues', 1);
        $this->tracker($class)->assertOk()->assertSee('1 of 3 paid');
        $this->assertSame('2026-06-01', $receipt->fresh()->paid_at->toDateString());
    }

    public function test_bank_payment_flows_into_revenue_reports_and_does_not_change_monthly_fee_dues(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $before = $student->computed_due_amount;
        $this->collect($class, $this->payload($class, $student, values: ['payment_method' => 'bank_transfer', 'bank_name' => 'Test Bank', 'bank_ref_no' => 'BANK-123']))->assertSessionHasNoErrors();
        $this->assertSame($before, $student->fresh()->computed_due_amount);
        $this->get(route('reports.bank_transactions'))->assertOk()->assertViewHas('totalIn', 1000.0);
        $this->get(route('dashboard', ['range' => 'today']))->assertOk()->assertViewHas('totalRevenue', 1000.0)
            ->assertViewHas('classFeeSummaries', fn ($data) => $data['exam']['collected'] === 1000.0 && $data['exam']['due'] === 2000.0 && $data['facility']['due'] === 2000.0);
    }

    public function test_unpaid_filter_and_search_find_only_matching_students(): void
    {
        $class = $this->schoolClass(['exam_fee_start_date' => '2026-10-20']);
        $paid = $this->student($class, ['name' => 'Paid pupil']);
        $unpaid = $this->student($class, ['name' => 'Unpaid pupil']);
        $this->collect($class, $this->payload($class, $paid, '2026-10-20'))->assertSessionHasNoErrors();
        $this->tracker($class, filters: ['status' => 'unpaid'])->assertOk()->assertSee('Unpaid pupil')->assertDontSee('Paid pupil');
        $this->tracker($class, filters: ['status' => 'paid'])->assertOk()->assertSee('Paid pupil')->assertDontSee('Unpaid pupil');
        $this->tracker($class, filters: ['q' => $unpaid->admission_number])->assertOk()->assertSee('Unpaid pupil')->assertDontSee('Paid pupil');
    }

    public function test_receipts_cannot_be_reassigned_or_created_without_an_installment(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $category = RevenueCategory::where('class_fee_type', 'exam')->firstOrFail();
        $this->post(route('revenue.items.store'), ['student_id' => $student->id, 'revenue_category_id' => $category->id, 'amount' => 1000, 'paid_at' => '2026-10-20'])
            ->assertSessionHasErrors('revenue_category_id');
        $this->collect($class, $this->payload($class, $student))->assertSessionHasNoErrors();
        $receipt = Revenue::first();
        $this->put(route('revenue.items.update', $receipt), ['amount' => 999])->assertSessionHasErrors('amount');
        $this->assertSame(1000.0, (float) $receipt->fresh()->amount);
        $this->get(route('revenue.categories.edit', $category))->assertRedirect(route('class-fees.index', ['type' => 'exam']));
        $this->put(route('revenue.categories.update', $category), ['default_amount' => 999])->assertSessionHasErrors('category');
    }

    public function test_permissions_distinguish_viewing_collecting_and_dashboard_widgets(): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff)->get(route('class-fees.index'))->assertForbidden();
        $staff->givePermissionTo(['class_fees.view', 'dashboard.view']);
        $this->get(route('class-fees.index'))->assertOk();
        $class = $this->schoolClass();
        $student = $this->student($class);
        $this->collect($class, $this->payload($class, $student))->assertForbidden();
        $this->get(route('dashboard'))->assertOk()->assertViewHas('classFeeSummaries', []);
        $staff->givePermissionTo('dashboard.widget.exam_fees.view');
        $this->get(route('dashboard'))->assertOk()->assertSee('data-fee-widget="exam"', false)->assertDontSee('data-fee-widget="facility"', false)
            ->assertViewHas('classFeeSummaries', fn ($data) => array_keys($data) === ['exam']);
        $staff->givePermissionTo('revenue.add');
        $this->collect($class, $this->payload($class, $student))->assertSessionHasNoErrors();
        $this->get(route('dashboard'))->assertOk()->assertSee('Collect Exam Fee')->assertSee('Collect Facility Fee');
    }

    public function test_foreign_students_and_fabricated_installments_are_rejected(): void
    {
        $class = $this->schoolClass();
        $otherClass = $this->schoolClass();
        $student = $this->student($otherClass);
        $this->collect($class, $this->payload($class, $student))->assertSessionHasErrors('cycle');
        $member = $this->student($class);
        $this->collect($class, $this->payload($class, $member, '2026-04-21'))->assertSessionHasErrors('cycle');
        $this->assertDatabaseCount('revenues', 0);
    }

    public function test_all_class_overview_and_all_years_include_earlier_unpaid_installments(): void
    {
        $class = $this->schoolClass(['exam_fee_start_date' => '2025-10-20']);
        $student = $this->student($class, ['joining_date' => '2025-01-01']);
        $other = $this->schoolClass();
        $this->student($other, ['name' => 'Another pupil']);
        $this->get(route('class-fees.index', ['all_years' => 1, 'status' => 'unpaid', 'include_facility' => 0]))->assertOk()
            ->assertViewHas('classSummaries', fn ($summaries) => $summaries->count() === 2)
            ->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 8000.0 && $tracker['unpaid_students'] === 2);
        $this->get(route('class-fees.index', ['q' => $student->admission_number]))->assertOk()
            ->assertViewHas('classSummaries', fn ($summaries) => $summaries->count() === 1 && $summaries->first()['class']->id === $class->id);
        $this->tracker($class, filters: ['all_years' => 1])->assertOk()->assertSee('2025-10-20', false)->assertSee('0 of 5 paid');
        $payload = $this->payload($class, $student, '2025-10-20');
        $this->tracker($class, filters: ['year' => 2025, 'cycle' => $payload['cycle'], 'student_id' => $student->id])
            ->assertOk()->assertSee('collect-fee', false)->assertSee('Record payment');
        $this->collect($class, $payload)->assertSessionHasNoErrors();
        $this->tracker($class, filters: ['all_years' => 1])->assertOk()->assertSee('1 of 5 paid');
    }

    public function test_collection_form_displays_the_selected_installment_and_prior_receipts(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $category = RevenueCategory::where('class_fee_type', 'exam')->first();
        Revenue::create(['student_id' => $student->id, 'revenue_category_id' => $category->id, 'amount' => 500, 'paid_at' => '2026-04-20', 'payment_status' => 'confirmed']);
        $payload = $this->payload($class, $student);
        $this->tracker($class, filters: ['cycle' => $payload['cycle'], 'student_id' => $student->id])->assertOk()
            ->assertSee('Record payment')->assertSee('Assign an earlier receipt')->assertSee('20 Apr 2026');
    }

    public function test_stopping_future_fees_keeps_due_installments_and_receipts(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $this->collect($class, $this->payload($class, $student))->assertSessionHasNoErrors();
        $this->put(route('classrooms.update', $class), ['name' => $class->name, 'active' => 1, 'exam_fee' => 0])->assertSessionHasNoErrors();
        $this->tracker($class)->assertOk()->assertSee('1 of 3 paid')
            ->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 2000.0);
        $this->tracker($class, filters: ['year' => 2027])->assertOk()
            ->assertViewHas('tracker', fn ($tracker) => $tracker['cycles']->isEmpty());
        $this->delete(route('classrooms.destroy', $class))->assertSessionHasErrors('classroom');
        $this->assertDatabaseHas('class_rooms', ['id' => $class->id]);
    }

    public function test_waivers_settle_the_installment_without_inflating_collected_income(): void
    {
        $class = $this->schoolClass(['exam_fee_start_date' => '2026-10-20']);
        $student = $this->student($class);
        $this->collect($class, $this->payload($class, $student, '2026-10-20', ['amount' => '500']))->assertSessionHasNoErrors();
        $this->post(route('revenue.items.waiver', Revenue::first()), ['amount' => 500, 'reason' => 'Approved fee concession'])->assertSessionHasNoErrors();
        $this->tracker($class)->assertOk()->assertSee('Waived / settled')->assertSee('1 settled with waiver')
            ->assertViewHas('tracker', fn ($tracker) => $tracker['paid'] === 500.0 && $tracker['due'] === 0.0);
        $this->get(route('dashboard', ['range' => 'today']))->assertOk()
            ->assertViewHas('classFeeSummaries', fn ($summaries) => $summaries['exam']['collected'] === 500.0 && $summaries['exam']['due'] === 0.0);
    }

    public function test_fee_rate_changes_preserve_month_end_dates_and_do_not_add_extra_installments(): void
    {
        $class = $this->schoolClass(['exam_fee_start_date' => '2026-01-31']);
        $student = $this->student($class);
        $this->collect($class, $this->payload($class, $student, '2026-01-31'))->assertSessionHasNoErrors();
        $this->put(route('classrooms.update', $class), ['name' => $class->name, 'exam_fee' => 1500, 'exam_fee_start_date' => '2026-11-01'])
            ->assertSessionHasErrors('exam_fee_start_date');
        $this->put(route('classrooms.update', $class), ['name' => $class->name, 'exam_fee' => 1500, 'exam_fee_start_date' => '2027-04-30'])
            ->assertSessionHasNoErrors();
        $this->tracker($class, filters: ['year' => 2027])->assertOk()
            ->assertViewHas('tracker', fn ($tracker) => $tracker['cycles']->map(fn ($cycle) => $cycle['due']->toDateString())->values()->all() === ['2027-01-31', '2027-04-30', '2027-07-31', '2027-10-31']);
    }

    public static function invalidCollections(): array
    {
        return [
            ['amount', '0'], ['amount', '-1'], ['amount', '1.001'], ['amount', '1001'],
            ['paid_at', '2026-10-21'], ['paid_at', 'invalid'], ['submission_id', ''],
            ['payment_method', 'bank_transfer'], ['payment_method', 'cheque'], ['cycle', 'invalid'],
        ];
    }

    #[DataProvider('invalidCollections')]
    public function test_invalid_collection_does_not_create_a_receipt(string $field, string $value): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $response = $this->collect($class, $this->payload($class, $student, values: [$field => $value]));
        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('revenues', 0);
    }

    public function test_all_exam_dates_and_facility_are_checked_by_default(): void
    {
        $class = $this->schoolClass();
        $this->student($class);
        $response = $this->get(route('class-fees.index', ['class_room_id' => $class->id, 'year' => 2026]))->assertOk()
            ->assertViewHas('includeFacility', true)
            ->assertViewHas('selectedExamDates', ['2026-04-20', '2026-07-20', '2026-10-20'])
            ->assertViewHas('tracker', fn ($tracker) => $tracker['cycles']->count() === 4 && $tracker['due'] === 5000.0)
            ->assertSee('Download PDF');
        $this->assertSame(3, preg_match_all('/name="exam_dates\[\]"[^>]*\bchecked\b/', $response->getContent()));
        $this->assertMatchesRegularExpression('/name="include_facility" value="1" checked/', $response->getContent());
    }

    public static function reportStatuses(): array
    {
        return [['paid', ['July payer'], 1000.0, 0.0], ['unpaid', ['April payer'], 0.0, 1000.0],
            ['all', ['April payer', 'July payer'], 1000.0, 1000.0]];
    }

    #[DataProvider('reportStatuses')]
    public function test_screen_and_pdf_apply_the_same_selected_exam_and_payment_status(string $status, array $expectedNames, float $paid, float $due): void
    {
        $class = $this->schoolClass();
        $july = $this->student($class, ['name' => 'July payer']);
        $april = $this->student($class, ['name' => 'April payer']);
        $this->collect($class, $this->payload($class, $july, '2026-07-20'))->assertSessionHasNoErrors();
        $this->collect($class, $this->payload($class, $april))->assertSessionHasNoErrors();
        $query = ['class_room_id' => $class->id, 'year' => 2026, 'status' => $status,
            'exam_dates' => ['2026-07-20'], 'include_facility' => 0];
        $this->get(route('class-fees.index', $query))->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->pluck('student.name')->values()->all() === $expectedNames)
            ->assertViewHas('tracker', fn ($tracker) => $tracker['paid'] === $paid && $tracker['due'] === $due && $tracker['cycles']->count() === 1);

        $engine = app('dompdf.wrapper');
        $captured = null;
        Pdf::shouldReceive('loadView')->once()->withArgs(function ($view, $data) use (&$captured) {
            $captured = $data;

            return $view === 'class-fees.pdf';
        })->andReturnUsing(fn ($view, $data) => $engine->loadView($view, $data));
        $response = $this->get(route('class-fees.index', $query + ['pdf' => 1]))->assertOk()
            ->assertDownload('class-fees-'.$status.'-2026.pdf')->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertSame($expectedNames, $captured['sections']->first()['rows']->pluck('student.name')->values()->all());
        $this->assertSame(['2026-07-20'], $captured['selectedExamDates']);
        $this->assertFalse($captured['includeFacility']);
        $this->assertSame($paid, $captured['tracker']['paid']);
        $this->assertSame($due, $captured['tracker']['due']);
    }

    public function test_facility_checkbox_changes_the_selected_balance_and_paid_list(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $this->collect($class, $this->payload($class, $student, '2026-07-20'))->assertSessionHasNoErrors();
        $query = ['class_room_id' => $class->id, 'exam_dates' => ['2026-07-20'], 'status' => 'paid'];
        $this->get(route('class-fees.index', $query + ['include_facility' => 0]))->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
        $this->get(route('class-fees.index', $query + ['include_facility' => 1]))->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->isEmpty());
        $this->collect($class, $this->payload($class, $student, '2026-01-15', type: 'facility'), 'facility')->assertSessionHasNoErrors();
        $this->get(route('class-fees.index', $query + ['include_facility' => 1]))->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
            ->assertViewHas('tracker', fn ($tracker) => $tracker['paid'] === 3000.0 && $tracker['due'] === 0.0);
    }

    public function test_clearing_all_checkboxes_keeps_the_selection_empty_and_facility_only_is_supported(): void
    {
        $class = $this->schoolClass();
        $this->student($class);
        $query = ['class_room_id' => $class->id, 'exam_selection' => 1];
        $this->get(route('class-fees.index', $query + ['include_facility' => 0]))->assertOk()
            ->assertViewHas('selectedExamDates', [])->assertViewHas('rows', fn ($rows) => $rows->isEmpty())
            ->assertViewHas('tracker', fn ($tracker) => $tracker['cycles']->isEmpty() && $tracker['due'] === 0.0);
        $this->get(route('class-fees.index', $query + ['include_facility' => 1]))->assertOk()
            ->assertViewHas('selectedExamDates', [])
            ->assertViewHas('tracker', fn ($tracker) => $tracker['cycles']->count() === 1 && $tracker['due'] === 2000.0);
        $this->get(route('class-fees.index', $query + ['include_facility' => 0, 'pdf' => 1]))->assertOk()->assertDownload();
    }

    public function test_changing_the_year_or_class_resets_the_exam_checkboxes_to_all_available_dates(): void
    {
        $class = $this->schoolClass();
        $this->student($class);
        $this->get(route('class-fees.index', ['class_room_id' => $class->id, 'year' => 2027,
            'exam_selection' => 1, 'exam_dates' => ['2026-07-20'], 'selection_scope' => 'exam:'.$class->id.':2026:0']))
            ->assertOk()->assertViewHas('selectedExamDates', ['2027-01-20', '2027-04-20', '2027-07-20', '2027-10-20']);
    }

    public function test_combined_fee_collection_uses_the_actual_fee_type_for_facility_installments(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $payload = $this->payload($class, $student, '2026-01-15', type: 'facility');
        $this->get(route('class-fees.index', ['class_room_id' => $class->id, 'cycle' => $payload['cycle'], 'student_id' => $student->id]))
            ->assertOk()->assertSee('Collect Facility Fee')
            ->assertSee(route('class-fees.store', ['classroom' => $class->id, 'type' => 'facility']), false);
    }

    public function test_pdf_download_requires_both_fee_access_and_report_download_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('class_fees.view');
        $this->actingAs($user)->get(route('class-fees.index'))->assertOk()->assertDontSee('Download PDF');
        $this->get(route('class-fees.index', ['pdf' => 1]))->assertForbidden();
        $user->givePermissionTo('reports.download');
        $this->get(route('class-fees.index', ['pdf' => 1]))->assertOk()->assertDownload();
        $user->revokePermissionTo('class_fees.view');
        $this->get(route('class-fees.index', ['pdf' => 1]))->assertForbidden();
    }

    public function test_all_class_pdf_contains_filtered_student_sections_and_handles_long_histories(): void
    {
        $class = $this->schoolClass();
        $class->update(['name' => 'Grade 1 - Combined Fees']);
        for ($i = 1; $i <= 42; $i++) {
            $this->student($class, ['name' => sprintf('Sample Student %02d Long Family Name', $i)]);
        }
        $other = $this->schoolClass(['exam_fee_start_date' => '2025-04-20', 'facility_fee_start_date' => '2025-01-15']);
        $other->update(['name' => 'Grade 2 - Fee History']);
        $student = $this->student($other, ['name' => 'Historical sample student', 'joining_date' => '2025-01-01']);
        $this->collect($other, $this->payload($other, $student, '2025-07-20', ['amount' => 500]))->assertSessionHasNoErrors();
        $response = $this->get(route('class-fees.index', ['pdf' => 1, 'all_years' => 1]))->assertOk()->assertDownload();
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        // Optional local visual QA output; the ordinary test run does not write preview files.
        $previewDir = getenv('CLASS_FEE_PDF_PREVIEW_DIR');
        if ($previewDir) {
            if (! is_dir($previewDir)) {
                mkdir($previewDir, 0775, true);
            }
            file_put_contents($previewDir.'/class-fees-layout.pdf', $response->getContent());
            $facility = $this->get(route('class-fees.index', ['pdf' => 1, 'type' => 'facility', 'class_room_id' => $class->id]))->assertOk();
            file_put_contents($previewDir.'/facility-only-layout.pdf', $facility->getContent());
            $selected = $this->get(route('class-fees.index', ['pdf' => 1, 'class_room_id' => $class->id, 'exam_dates' => ['2026-07-20'], 'include_facility' => 0]))->assertOk();
            file_put_contents($previewDir.'/selected-exam-layout.pdf', $selected->getContent());
        }
    }
}
