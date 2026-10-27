<?php

namespace Tests\Feature;

use App\Models\ClassFeePaymentHistory;
use App\Models\ClassRoom;
use App\Models\Revenue;
use App\Models\Student;
use App\Models\StudentPromotionHistory;
use App\Models\User;
use App\Services\Billing\ClassFeePaymentService;
use App\Services\Billing\ClassFeeService;
use App\Services\CashBalanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClassFeeBulkPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->forgetInstance('settings');
        $this->travelTo(Carbon::parse('2026-10-20 12:00:00'));
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('Admin', 'web'));
        $this->actingAs($user);
    }

    private function terms(int $year = 2026): array
    {
        return [['start' => "$year-01-01", 'end' => "$year-04-30"],
            ['start' => "$year-05-01", 'end' => "$year-08-31"],
            ['start' => "$year-09-01", 'end' => "$year-12-31"]];
    }

    private function schoolClass(bool $legacy = false): ClassRoom
    {
        $class = ClassRoom::create(['name' => 'Class '.Str::random(6), 'active' => true]);
        app(ClassFeeService::class)->configure($class, ['exam_fee' => 1000,
            'exam_fee_start_date' => $legacy ? '2026-04-20' : '2026-01-01',
            'facility_fee' => 2000, 'facility_fee_start_date' => '2026-01-15']
            + ($legacy ? [] : ['exam_terms' => $this->terms()]));

        return $class;
    }

    private function student(ClassRoom $class, array $values = []): Student
    {
        return Student::create($values + ['name' => 'Student '.Str::random(6), 'admission_number' => Str::random(8),
            'class_room_id' => $class->id, 'joining_date' => '2025-01-01', 'active' => true, 'alumni' => false]);
    }

    private function line(ClassRoom $class, Student $student, string $date = '2026-01-01', bool $income = true, float $amount = 1000, string $type = 'exam'): array
    {
        return ['student_id' => $student->id, 'class_room_id' => $class->id,
            'cycle' => $class->feeSchedules()->where('fee_type', $type)->whereNull('ends_on')->firstOrFail()->id.':'.$date,
            'amount' => (string) $amount, 'record_revenue' => (int) $income];
    }

    private function batch(array $lines, array $extra = []): array
    {
        return $extra + ['submission_id' => (string) Str::uuid(), 'year' => 2026,
            'paid_at' => '2026-06-01', 'payment_method' => 'cash', 'lines' => $lines];
    }

    private function tracker(ClassRoom $class): array
    {
        return app(ClassFeeService::class)->tracker($class->fresh()->feeSchedules->where('fee_type', 'exam'),
            $class->students()->with('promotionHistories')->get(), Carbon::parse('2026-01-01'), Carbon::parse('2026-12-31'));
    }

    private function correction(array $extra = []): array
    {
        return $extra + ['amount' => 600, 'paid_at' => '2026-05-01', 'record_revenue' => 1, 'notes' => 'Corrected payment'];
    }

    public function test_three_terms_are_due_from_their_start_and_overdue_only_after_the_end(): void
    {
        $class = $this->schoolClass();
        $this->student($class);
        $this->travelTo(Carbon::parse('2025-12-31'));
        $this->assertSame(0.0, $this->tracker($class)['due']);
        $this->travelTo(Carbon::parse('2026-01-01'));
        $tracker = $this->tracker($class);
        $this->assertCount(3, $tracker['cycles']);
        $this->assertSame(1000.0, $tracker['due']);
        $this->assertSame('Unpaid', $tracker['rows']->first()['installments']->first()['status']);
        $this->travelTo(Carbon::parse('2026-04-30'));
        $this->assertSame('Unpaid', $this->tracker($class)['rows']->first()['installments']->first()['status']);
        $this->travelTo(Carbon::parse('2026-05-01'));
        $this->assertSame(2000.0, $this->tracker($class)['due']);
        $this->assertSame('Overdue', $this->tracker($class)['rows']->first()['installments']->first()['status']);
        $this->get(route('class-fees.index', ['class_room_id' => $class->id]))->assertOk()->assertSee('Term 1')->assertSee('30 Apr 2026');
    }

    public static function termAdmissionDates(): array
    {
        return [
            ['2026-01-01', [true, true, true]],
            ['2026-04-30', [true, true, true]],
            ['2026-05-01', [false, true, true]],
            ['2026-08-31', [false, true, true]],
            ['2026-09-01', [false, false, true]],
            ['2026-10-15', [false, false, true]],
            ['2026-12-31', [false, false, true]],
            ['2027-01-01', [false, false, false]],
        ];
    }

    #[DataProvider('termAdmissionDates')]
    public function test_students_owe_the_term_they_join_in_and_later_terms(string $joined, array $expected): void
    {
        $this->travelTo(Carbon::parse('2026-12-31'));
        $class = $this->schoolClass();
        $this->student($class, ['joining_date' => $joined]);
        $tracker = $this->tracker($class);
        $this->assertSame($expected, $tracker['rows']->first()['installments']->pluck('eligible')->values()->all());
        $this->assertSame(1000.0 * count(array_filter($expected)), $tracker['due']);
    }

    public function test_admissions_use_the_saved_term_end_including_gaps_and_cross_year_terms(): void
    {
        $class = ClassRoom::create(['name' => 'Custom term dates']);
        app(ClassFeeService::class)->configure($class, ['exam_fee' => 1000, 'exam_terms' => [
            ['start' => '2025-09-01', 'end' => '2026-01-10'],
            ['start' => '2026-01-15', 'end' => '2026-04-30'],
            ['start' => '2026-05-05', 'end' => '2026-08-31'],
        ]]);
        $student = $this->student($class, ['joining_date' => '2026-01-10']);
        $fees = app(ClassFeeService::class);
        $check = fn ($member) => $fees->tracker($class->feeSchedules, collect([$member]),
            Carbon::parse('2025-09-01'), Carbon::parse('2026-08-31'))['rows']->first()['installments']->pluck('eligible')->values()->all();
        $this->assertSame([true, true, true], $check($student));
        $student->update(['joining_date' => '2026-01-11']);
        $this->assertSame([false, true, true], $check($student));
    }

    public function test_students_joining_during_term_three_can_pay_individually_and_in_bulk(): void
    {
        $class = $this->schoolClass();
        $first = $this->student($class, ['joining_date' => '2026-10-01']);
        $second = $this->student($class, ['joining_date' => '2026-10-10']);
        $this->get(route('class-fees.bulk', ['class_room_id' => $class->id, 'year' => 2026]))->assertOk()
            ->assertViewHas('candidates', fn ($candidates) => $candidates->count() === 2
                && $candidates->every(fn ($candidate) => collect($candidate['installments'])->where('type', 'exam')->pluck('term')->all() === [3]));
        $this->post(route('class-fees.store', ['classroom' => $class, 'type' => 'exam']),
            $this->line($class, $first, '2026-09-01') + ['submission_id' => (string) Str::uuid(),
                'paid_at' => '2026-10-20', 'payment_method' => 'cash'])->assertSessionHasNoErrors();
        $this->post(route('class-fees.bulk.store'), $this->batch([
            $this->line($class, $second, '2026-09-01', false),
        ], ['paid_at' => '2026-10-20']))->assertSessionHasNoErrors();
        $this->post(route('class-fees.bulk.store'), $this->batch([
            $this->line($class, $second, '2026-05-01'),
        ]))->assertSessionHasErrors();
        $tracker = $this->tracker($class);
        $this->assertSame(2000.0, $tracker['paid']);
        $this->assertSame(0.0, $tracker['due']);
        $this->assertDatabaseCount('revenues', 1);
        $this->assertDatabaseCount('class_fee_payment_histories', 1);
    }

    public function test_a_new_admission_transferring_during_the_joining_term_has_only_one_term_charge(): void
    {
        $old = $this->schoolClass();
        $new = $this->schoolClass();
        $student = $this->student($new, ['joining_date' => '2026-10-01']);
        foreach ([['from' => null, 'to' => $old->id, 'date' => '2026-10-02'],
            ['from' => $old->id, 'to' => $new->id, 'date' => '2026-10-10']] as $change) {
            $move = StudentPromotionHistory::create(['student_id' => $student->id, 'from_class_room_id' => $change['from'],
                'to_class_room_id' => $change['to'], 'action' => 'transfer']);
            $move->forceFill(['created_at' => Carbon::parse($change['date'])])->save();
        }
        $fees = app(ClassFeeService::class);
        $status = fn ($class) => $fees->tracker($class->feeSchedules->where('fee_type', 'exam'),
            collect([$student->fresh()]), Carbon::parse('2026-01-01'), Carbon::parse('2026-12-31'));
        $this->assertSame(1000.0, $status($old)['due']);
        $this->assertSame(0.0, $status($new)['due']);
    }

    public function test_term_calendar_repeats_three_times_per_year_and_can_cross_calendar_years(): void
    {
        $class = $this->schoolClass();
        $fees = app(ClassFeeService::class);
        $cycles = $fees->cycles($class->feeSchedules->where('fee_type', 'exam'), Carbon::parse('2026-01-01'), Carbon::parse('2028-12-31'));
        $this->assertCount(9, $cycles);
        $class = ClassRoom::create(['name' => 'September year']);
        $fees->configure($class, ['exam_fee' => 1000, 'exam_terms' => [
            ['start' => '2026-09-01', 'end' => '2026-12-31'],
            ['start' => '2027-01-01', 'end' => '2027-04-30'],
            ['start' => '2027-05-01', 'end' => '2027-08-31']]]);
        $cycles = $fees->cycles($class->feeSchedules, Carbon::parse('2027-01-01'), Carbon::parse('2027-12-31'));
        $this->assertSame([2, 3, 1], $cycles->pluck('term')->values()->all());
        $this->assertSame(['2027-01-01', '2027-05-01', '2027-09-01'], $cycles->map(fn ($cycle) => $cycle['due']->toDateString())->values()->all());
    }

    public function test_class_form_validates_term_dates_and_preserves_manual_overrides(): void
    {
        $terms = $this->terms();
        $terms[1]['start'] = '2026-05-05';
        $this->post(route('classrooms.store'), ['name' => 'Manual calendar', 'exam_fee' => 1000, 'exam_fee_start_date' => '2026-01-01', 'exam_terms' => $terms])
            ->assertSessionHasNoErrors();
        $class = ClassRoom::where('name', 'Manual calendar')->firstOrFail();
        $this->assertSame($terms, $class->feeSchedules->first()->terms);
        $this->get(route('classrooms.edit', $class))->assertOk()->assertSee('Use three editable terms');
        $terms[1]['start'] = '2026-04-30';
        $this->put(route('classrooms.update', $class), ['name' => 'Must not save', 'exam_fee' => 1000, 'exam_fee_start_date' => '2026-01-01', 'exam_terms' => $terms])->assertSessionHasErrors('exam_terms');
        $this->assertSame('Manual calendar', $class->fresh()->name);
    }

    public function test_legacy_receipts_follow_their_terms_when_a_three_term_calendar_is_set(): void
    {
        $class = $this->schoolClass(true);
        $student = $this->student($class);
        $service = app(ClassFeePaymentService::class);
        foreach (['2026-04-20', '2026-07-20'] as $date) {
            $line = $this->line($class, $student, $date);
            $service->collect($class, 'exam', $line + ['submission_id' => (string) Str::uuid(), 'paid_at' => '2026-10-20'], auth()->user());
        }
        $this->put(route('classrooms.update', $class), ['name' => $class->name, 'exam_fee' => 1000, 'exam_fee_start_date' => '2026-01-01', 'exam_terms' => $this->terms()])->assertSessionHasNoErrors();
        $this->assertSame(['2026-01-01', '2026-05-01'], Revenue::orderBy('id')->get()->map(fn ($revenue) => $revenue->class_fee_due_date->toDateString())->all());
        $tracker = $this->tracker($class);
        $this->assertSame(2000.0, $tracker['paid']);
        $this->assertSame(1000.0, $tracker['due']);
        $this->assertDatabaseCount('revenues', 2);
    }

    public function test_mixed_bulk_payments_update_balances_but_only_checked_payments_increase_income(): void
    {
        $class = $this->schoolClass();
        $first = $this->student($class);
        $second = $this->student($class);
        $lines = [$this->line($class, $first), $this->line($class, $first, '2026-05-01', false), $this->line($class, $second, amount: 400)];
        $this->post(route('class-fees.bulk.store'), $this->batch($lines))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('revenues', 2);
        $this->assertDatabaseCount('class_fee_payment_histories', 1);
        $this->assertDatabaseCount('class_fee_batches', 1);
        $this->assertSame(1400.0, app(CashBalanceService::class)->balanceOn('2026-10-20'));
        $this->assertSame(2400.0, $this->tracker($class)['paid']);
        $this->assertSame(3600.0, $this->tracker($class)['due']);
        $this->get(route('class-fees.index', ['class_room_id' => $class->id]))->assertOk()->assertSee('no income')->assertSee('Edit / delete');
        $this->get(route('reports.daily_ledger', ['from' => '2026-06-01', 'to' => '2026-06-01']))->assertOk()->assertViewHas('totalIn', 1400.0);
    }

    public function test_historical_first_and_second_terms_settle_without_any_revenue_or_ledger_effect(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class, ['active' => false, 'alumni' => true]);
        $lines = [$this->line($class, $student, income: false), $this->line($class, $student, '2026-05-01', false)];
        $payload = $this->batch($lines, ['paid_at' => '2026-05-10']);
        unset($payload['lines']);
        $payload['lines_payload'] = json_encode($lines);
        $this->post(route('class-fees.bulk.store'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('revenues', 0);
        $this->get(route('reports.daily_ledger', ['from' => '2026-05-10', 'to' => '2026-05-10']))->assertOk()->assertViewHas('totalIn', 0.0);
        $this->assertSame(0.0, app(CashBalanceService::class)->balanceOn('2026-10-20'));
        $tracker = $this->tracker($class);
        $this->assertSame(2000.0, $tracker['paid']);
        $this->assertSame(1000.0, $tracker['due']);
        $this->get(route('class-fees.bulk', ['include_inactive' => 1]))->assertOk()
            ->assertViewHas('candidates', fn ($candidates) => $candidates->first()['installments'][0]['term'] === 3);
    }

    public function test_bulk_payment_retry_is_idempotent_and_reusing_a_batch_with_changed_details_is_rejected(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $payload = $this->batch([$this->line($class, $student)]);
        $this->post(route('class-fees.bulk.store'), $payload)->assertSessionHasNoErrors();
        $this->post(route('class-fees.bulk.store'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('revenues', 1);
        $payload['lines'][0]['amount'] = '500';
        $this->post(route('class-fees.bulk.store'), $payload)->assertSessionHasErrors('submission_id');
        $this->assertSame(1000.0, (float) Revenue::sum('amount'));
    }

    public function test_any_invalid_balance_rolls_back_the_entire_batch_and_its_audit_entries(): void
    {
        $class = $this->schoolClass();
        $first = $this->student($class);
        $second = $this->student($class);
        $payload = $this->batch([$this->line($class, $first), $this->line($class, $second, amount: 1001)]);
        $this->post(route('class-fees.bulk.store'), $payload)->assertSessionHasErrors();
        $this->assertDatabaseCount('revenues', 0);
        $this->assertDatabaseCount('class_fee_batches', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $payload['lines'][1]['amount'] = '1000';
        $this->post(route('class-fees.bulk.store'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('revenues', 2);
    }

    public function test_duplicate_rows_wrong_classes_and_non_applicable_students_are_rejected(): void
    {
        $class = $this->schoolClass();
        $other = $this->schoolClass();
        $student = $this->student($class);
        $line = $this->line($class, $student);
        $this->post(route('class-fees.bulk.store'), $this->batch([$line, $line]))->assertSessionHasErrors('lines');
        $line['class_room_id'] = $other->id;
        $this->post(route('class-fees.bulk.store'), $this->batch([$line]))->assertSessionHasErrors();
        $line = $this->line($class, $this->student($other));
        $this->post(route('class-fees.bulk.store'), $this->batch([$line]))->assertSessionHasErrors();
        $this->assertDatabaseCount('revenues', 0);
    }

    public function test_students_from_different_classes_and_facility_fees_can_share_a_batch(): void
    {
        $first = $this->schoolClass();
        $second = $this->schoolClass();
        $lines = [$this->line($first, $this->student($first)),
            $this->line($second, $this->student($second), '2026-01-15', true, 2000, 'facility')];
        $this->post(route('class-fees.bulk.store'), $this->batch($lines, ['payment_method' => 'bank_transfer', 'bank_name' => 'Test bank']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('revenues', 2);
        $this->assertSame(3000.0, (float) Revenue::sum('amount'));
        $this->assertSame('Test bank', Revenue::first()->payment_meta['bank']);
        $this->get(route('class-fees.bulk'))->assertOk()->assertSee('Select all matching students')->assertSee('Search students');
    }

    public function test_late_joiners_can_bulk_pay_facility_fees_with_optional_income_and_correct_or_delete_them(): void
    {
        $class = $this->schoolClass();
        $first = $this->student($class, ['joining_date' => '2026-08-01']);
        $second = $this->student($class, ['joining_date' => '2026-10-01']);
        $this->get(route('class-fees.bulk', ['class_room_id' => $class->id, 'year' => 2026]))->assertOk()
            ->assertViewHas('candidates', fn ($candidates) => $candidates->count() === 2
                && $candidates->every(fn ($candidate) => collect($candidate['installments'])->contains(
                    fn ($installment) => $installment['type'] === 'facility' && $installment['balance'] === 2000.0)));
        $lines = [$this->line($class, $first, '2026-01-15', true, 2000, 'facility'),
            $this->line($class, $second, '2026-01-15', false, 2000, 'facility')];
        $this->post(route('class-fees.bulk.store'), $this->batch($lines, ['paid_at' => '2026-10-20']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('revenues', 1);
        $this->assertDatabaseCount('class_fee_payment_histories', 1);
        $this->assertSame(2000.0, app(CashBalanceService::class)->balanceOn('2026-10-20'));
        $payment = Revenue::firstOrFail();
        $this->put(route('class-fees.payments.update', ['source' => 'revenue', 'payment' => $payment->id]),
            $this->correction(['amount' => 1500, 'record_revenue' => 0]))->assertSessionHasNoErrors();
        $this->assertSame(0.0, app(CashBalanceService::class)->balanceOn('2026-10-20'));
        $history = ClassFeePaymentHistory::where('student_id', $first->id)->firstOrFail();
        $this->put(route('class-fees.payments.update', ['source' => 'history', 'payment' => $history->id]),
            $this->correction(['amount' => 1500]))->assertSessionHasNoErrors();
        $payment = Revenue::where('student_id', $first->id)->latest('id')->firstOrFail();
        $this->delete(route('class-fees.payments.destroy', ['source' => 'revenue', 'payment' => $payment->id]),
            ['reason' => 'Incorrect annual payment'])->assertSessionHasNoErrors();
        $this->get(route('class-fees.index', ['class_room_id' => $class->id, 'type' => 'facility', 'year' => 2026]))->assertOk()
            ->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 2000.0 && $tracker['paid'] === 2000.0);
        $this->assertSame(0.0, app(CashBalanceService::class)->balanceOn('2026-10-20'));
    }

    public function test_existing_facility_receipts_retain_their_class_through_corrections_and_cancellation(): void
    {
        $old = $this->schoolClass();
        $new = $this->schoolClass();
        $student = $this->student($new);
        $move = StudentPromotionHistory::create(['student_id' => $student->id, 'from_class_room_id' => $old->id, 'to_class_room_id' => $new->id, 'action' => 'transfer']);
        $move->forceFill(['created_at' => Carbon::parse('2026-01-10')])->save();
        // The previous due-date rule allowed this charge in the new class on January 15.
        $schedule = $new->feeSchedules()->where('fee_type', 'facility')->firstOrFail();
        $history = ClassFeePaymentHistory::create(['student_id' => $student->id, 'class_fee_schedule_id' => $schedule->id,
            'class_fee_due_date' => '2026-01-15', 'class_fee_submission_id' => (string) Str::uuid(),
            'amount' => 2000, 'paid_at' => '2026-06-01', 'payment_method' => 'cash', 'created_by' => auth()->id()]);
        $this->put(route('class-fees.payments.update', ['source' => 'history', 'payment' => $history->id]),
            $this->correction(['amount' => 1800]))->assertSessionHasNoErrors();
        $payment = Revenue::firstOrFail();
        $this->delete(route('class-fees.payments.destroy', ['source' => 'revenue', 'payment' => $payment->id]),
            ['reason' => 'Wrong amount'])->assertSessionHasNoErrors();
        $this->get(route('class-fees.index', ['class_room_id' => $old->id, 'type' => 'facility', 'year' => 2026]))->assertOk()
            ->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 0.0);
        $this->get(route('class-fees.index', ['class_room_id' => $new->id, 'type' => 'facility', 'year' => 2026]))->assertOk()
            ->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 2000.0 && $tracker['paid'] === 0.0);
    }

    public function test_editing_amount_and_date_updates_income_and_reopens_the_balance(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $this->post(route('class-fees.bulk.store'), $this->batch([$this->line($class, $student)]))->assertSessionHasNoErrors();
        $payment = Revenue::firstOrFail();
        $this->get(route('revenue.items.edit', $payment))->assertRedirect(route('class-fees.payments.edit', ['source' => 'revenue', 'payment' => $payment->id]));
        $this->get(route('class-fees.payments.edit', ['source' => 'revenue', 'payment' => $payment->id]))->assertOk()->assertSee('Include in revenue');
        $this->put(route('class-fees.payments.update', ['source' => 'revenue', 'payment' => $payment->id]), $this->correction())->assertSessionHasNoErrors();
        $this->assertSame(600.0, (float) $payment->fresh()->amount);
        $this->assertSame(600.0, app(CashBalanceService::class)->balanceOn('2026-05-01'));
        $this->assertSame(2400.0, $this->tracker($class)['due']);
    }

    public function test_unchecking_revenue_on_edit_preserves_settlement_and_removes_income_and_can_be_reversed(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $this->post(route('class-fees.bulk.store'), $this->batch([$this->line($class, $student)]))->assertSessionHasNoErrors();
        $payment = Revenue::firstOrFail();
        $this->put(route('class-fees.payments.update', ['source' => 'revenue', 'payment' => $payment->id]), $this->correction(['amount' => 1000, 'record_revenue' => 0]))->assertSessionHasNoErrors();
        $this->assertTrue($payment->fresh()->isCancelled());
        $this->assertSame(0.0, app(CashBalanceService::class)->balanceOn('2026-10-20'));
        $this->assertSame(1000.0, $this->tracker($class)['paid']);
        $history = ClassFeePaymentHistory::firstOrFail();
        $this->put(route('class-fees.payments.update', ['source' => 'history', 'payment' => $history->id]), $this->correction(['amount' => 800]))->assertSessionHasNoErrors();
        $this->assertTrue($history->fresh()->isCancelled());
        $this->assertSame(800.0, $this->tracker($class)['paid']);
        $this->assertSame(800.0, app(CashBalanceService::class)->balanceOn('2026-10-20'));
        $this->assertDatabaseCount('revenues', 2);
    }

    public function test_editing_a_historical_amount_keeps_it_out_of_income_and_deletion_reopens_the_term(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $this->post(route('class-fees.bulk.store'), $this->batch([$this->line($class, $student, income: false)]))->assertSessionHasNoErrors();
        $history = ClassFeePaymentHistory::firstOrFail();
        $route = ['source' => 'history', 'payment' => $history->id];
        $this->get(route('class-fees.payments.edit', $route))->assertOk()->assertSee('Historical payment');
        $this->put(route('class-fees.payments.update', $route), $this->correction(['record_revenue' => 0]))->assertSessionHasNoErrors();
        $this->assertSame(600.0, $this->tracker($class)['paid']);
        $this->delete(route('class-fees.payments.destroy', $route), ['reason' => 'Wrong historical entry'])->assertSessionHasNoErrors();
        $this->assertSame(0.0, $this->tracker($class)['paid']);
        $this->assertSame(3000.0, $this->tracker($class)['due']);
        $this->assertDatabaseCount('revenues', 0);
        $this->assertTrue($history->fresh()->isCancelled());
    }

    public function test_revenue_payment_deletion_removes_income_and_retains_audit_and_bill_number(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $this->post(route('class-fees.bulk.store'), $this->batch([$this->line($class, $student)]))->assertSessionHasNoErrors();
        $payment = Revenue::firstOrFail();
        $bill = $payment->bill_no;
        $this->delete(route('class-fees.payments.destroy', ['source' => 'revenue', 'payment' => $payment->id]), ['reason' => 'Incorrect student payment'])->assertSessionHasNoErrors();
        $this->assertSame($bill, $payment->fresh()->bill_no);
        $this->assertSame(0.0, app(CashBalanceService::class)->balanceOn('2026-10-20'));
        $this->assertSame(3000.0, $this->tracker($class)['due']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'class_fee.payment_deleted']);
    }

    public function test_edits_cannot_overpay_when_other_receipts_already_settle_part_of_the_term(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        foreach ([400, 600] as $amount) {
            $this->post(route('class-fees.bulk.store'), $this->batch([$this->line($class, $student, amount: $amount)]))->assertSessionHasNoErrors();
        }
        $payment = Revenue::firstOrFail();
        $this->put(route('class-fees.payments.update', ['source' => 'revenue', 'payment' => $payment->id]), $this->correction())->assertSessionHasErrors('amount');
        $this->assertSame(400.0, (float) $payment->fresh()->amount);
    }

    public function test_view_collection_edit_and_delete_permissions_are_enforced(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $payload = $this->batch([$this->line($class, $student)]);
        $this->post(route('class-fees.bulk.store'), $payload)->assertSessionHasNoErrors();
        $payment = Revenue::firstOrFail();
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('class_fees.view');
        $this->actingAs($viewer);
        $this->get(route('class-fees.bulk'))->assertForbidden();
        $this->post(route('class-fees.bulk.store'), $payload)->assertForbidden();
        $this->get(route('class-fees.payments.edit', ['source' => 'revenue', 'payment' => $payment->id]))->assertForbidden();
        $viewer->givePermissionTo('revenue.manage');
        $this->get(route('class-fees.payments.edit', ['source' => 'revenue', 'payment' => $payment->id]))->assertOk();
        $this->delete(route('class-fees.payments.destroy', ['source' => 'revenue', 'payment' => $payment->id]), ['reason' => 'Wrong payment'])->assertForbidden();
    }

    public function test_malformed_json_and_future_dates_do_not_create_a_batch(): void
    {
        $this->post(route('class-fees.bulk.store'), ['lines_payload' => '{bad'])->assertSessionHasErrors('lines');
        $class = $this->schoolClass();
        $student = $this->student($class);
        $this->post(route('class-fees.bulk.store'), $this->batch([$this->line($class, $student)], ['paid_at' => '2026-10-21']))->assertSessionHasErrors('paid_at');
        $this->assertDatabaseCount('class_fee_batches', 0);
    }

    public function test_future_rate_change_preserves_the_old_terms_and_rejects_overlapping_calendars(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $this->post(route('class-fees.bulk.store'), $this->batch([$this->line($class, $student)]))->assertSessionHasNoErrors();
        $overlap = $this->terms(2027);
        $overlap[0]['start'] = '2026-11-01';
        $this->put(route('classrooms.update', $class), ['name' => $class->name, 'exam_fee' => 1500,
            'exam_fee_start_date' => '2026-11-01', 'exam_terms' => $overlap])->assertSessionHasErrors('exam_terms');
        $this->put(route('classrooms.update', $class), ['name' => $class->name, 'exam_fee' => 1500,
            'exam_fee_start_date' => '2027-01-01', 'exam_terms' => $this->terms(2027)])->assertSessionHasNoErrors();
        $this->assertSame(1000.0, $this->tracker($class)['paid']);
        $cycles = app(ClassFeeService::class)->cycles($class->fresh()->feeSchedules->where('fee_type', 'exam'), Carbon::parse('2027-01-01'), Carbon::parse('2027-12-31'));
        $this->assertCount(3, $cycles);
        $this->assertTrue($cycles->every(fn ($cycle) => $cycle['amount'] === 1500.0));
    }

    public function test_correcting_later_year_calendar_keeps_previous_year_history(): void
    {
        $class = ClassRoom::create(['name' => 'Multi-year legacy']);
        app(ClassFeeService::class)->configure($class, ['exam_fee' => 1000, 'exam_fee_start_date' => '2025-04-20']);
        $student = $this->student($class);
        foreach (['2025-04-20', '2026-01-20'] as $date) {
            app(ClassFeePaymentService::class)->collect($class, 'exam', $this->line($class, $student, $date)
                + ['submission_id' => (string) Str::uuid(), 'paid_at' => '2026-10-20'], auth()->user());
        }
        $this->put(route('classrooms.update', $class), ['name' => $class->name, 'exam_fee' => 1000,
            'exam_fee_start_date' => '2026-01-01', 'exam_terms' => $this->terms()])->assertSessionHasNoErrors();
        $payments = Revenue::orderBy('id')->get();
        $this->assertSame('2025-04-20', $payments[0]->class_fee_due_date->toDateString());
        $this->assertSame('2026-01-01', $payments[1]->class_fee_due_date->toDateString());
        $this->assertNotSame($payments[0]->class_fee_schedule_id, $payments[1]->class_fee_schedule_id);
        $this->assertSame(1000.0, $this->tracker($class)['paid']);
    }

    public function test_cancelling_a_fourth_legacy_receipt_allows_safe_three_term_conversion(): void
    {
        $class = ClassRoom::create(['name' => 'Four legacy quarters']);
        app(ClassFeeService::class)->configure($class, ['exam_fee' => 1000, 'exam_fee_start_date' => '2026-01-01']);
        $student = $this->student($class);
        app(ClassFeePaymentService::class)->collect($class, 'exam', $this->line($class, $student, '2026-10-01')
            + ['submission_id' => (string) Str::uuid(), 'paid_at' => '2026-10-20'], auth()->user());
        $payload = ['name' => $class->name, 'exam_fee' => 1000, 'exam_fee_start_date' => '2026-01-01', 'exam_terms' => $this->terms()];
        $this->put(route('classrooms.update', $class), $payload)->assertSessionHasErrors('exam_terms');
        $this->assertNull($class->fresh()->feeSchedules->first()->terms);
        $payment = Revenue::firstOrFail();
        $this->delete(route('class-fees.payments.destroy', ['source' => 'revenue', 'payment' => $payment->id]), ['reason' => 'Quarterly duplicate'])->assertSessionHasNoErrors();
        $this->put(route('classrooms.update', $class), $payload)->assertSessionHasNoErrors();
        $this->assertCount(3, $this->tracker($class)['cycles']);
        $this->assertSame(3000.0, $this->tracker($class)['due']);
    }

    public function test_historical_receipts_prevent_class_deletion_and_pending_cheques_still_reserve_bulk_balances(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $this->post(route('class-fees.bulk.store'), $this->batch([$this->line($class, $student, income: false)]))->assertSessionHasNoErrors();
        $this->delete(route('classrooms.destroy', $class))->assertSessionHasErrors('classroom');
        $line = $this->line($class, $student, '2026-05-01');
        $this->post(route('class-fees.store', ['classroom' => $class->id, 'type' => 'exam']), $line + [
            'submission_id' => (string) Str::uuid(), 'paid_at' => '2026-10-20', 'payment_method' => 'cheque',
            'cheque_bank' => 'Test bank', 'cheque_number' => '12345', 'cheque_date' => '2026-10-21',
        ])->assertSessionHasNoErrors();
        $this->post(route('class-fees.bulk.store'), $this->batch([$line]))->assertSessionHasErrors();
        $this->assertDatabaseCount('revenues', 1);
        $this->assertSame(1000.0, $this->tracker($class)['pending'] ?? $this->tracker($class)['rows']->sum('pending'));
    }

    public function test_class_form_and_bulk_page_load_the_standalone_fee_script_before_their_alpine_state(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        foreach ([route('classrooms.edit', $class), route('classrooms.create'), route('class-fees.bulk'), route('class-fees.bulk', ['mode' => 'unpaid'])] as $url) {
            $response = $this->get($url)->assertOk()->assertSee('<script data-class-fee-runtime=', false);
            $html = $response->getContent();
            $scriptAt = strpos($html, '<script data-class-fee-runtime=');
            $stateAt = strpos($html, 'x-data="{ ...termCalendar');
            if ($stateAt === false) {
                $stateAt = strpos($html, 'x-data="schoolFeeForms.payments(');
            }
            if ($stateAt === false) {
                $stateAt = strpos($html, 'x-data="schoolFeeForms.reversals(');
            }
            $this->assertNotFalse($stateAt);
            $this->assertLessThan($stateAt, $scriptAt);
            $this->assertSame(1, substr_count($html, '<script data-class-fee-runtime='));
            $this->assertStringContainsString(file_get_contents(base_path('public/js/class-fees.js')), $html);
        }
    }

    public function test_fee_forms_embed_the_current_application_runtime_even_with_a_stale_custom_public_copy(): void
    {
        $originalPublicPath = public_path();
        $stalePublicPath = sys_get_temp_dir().'/school-stale-public-'.Str::uuid();
        mkdir($stalePublicPath.'/js', 0777, true);
        file_put_contents($stalePublicPath.'/js/class-fees.js', 'window.bulkClassFees = () => ({ installment: "exam" });');
        try {
            $this->app->usePublicPath($stalePublicPath);
            $html = view('class-fees.partials.scripts')->render();
            $this->assertStringContainsString('globalThis.schoolFeeForms =', $html);
            $this->assertStringContainsString('selectTerms(all)', $html);
            $this->assertStringNotContainsString('src=', $html);
            $this->assertStringNotContainsString('installment: "exam"', $html);
        } finally {
            $this->app->usePublicPath($originalPublicPath);
            unlink($stalePublicPath.'/js/class-fees.js');
            rmdir($stalePublicPath.'/js');
            rmdir($stalePublicPath);
        }
    }

    public function test_missing_application_runtime_shows_an_upload_message_instead_of_a_server_error(): void
    {
        $originalBasePath = base_path();
        try {
            $this->app->setBasePath(sys_get_temp_dir().'/school-missing-runtime-'.Str::uuid());
            $html = view('class-fees.partials.scripts')->render();
            $this->assertStringContainsString('The fee form could not load.', $html);
            $this->assertStringContainsString('public/js/class-fees.js', $html);
            $this->assertStringNotContainsString('<script data-class-fee-runtime=', $html);
        } finally {
            $this->app->setBasePath($originalBasePath);
        }
    }

    public function test_unpaid_facility_fee_can_change_amount_and_starting_date_after_its_schedule_has_started(): void
    {
        $class = $this->schoolClass();
        $this->student($class);
        $schedule = $class->feeSchedules()->where('fee_type', 'facility')->firstOrFail();
        $this->put(route('classrooms.update', $class), ['name' => $class->name,
            'facility_fee' => 5000, 'facility_fee_start_date' => '2026-01-02'])->assertSessionHasNoErrors();
        $this->assertSame('2026-01-02', $schedule->fresh()->first_due_date->toDateString());
        $this->assertSame(5000.0, (float) $schedule->fresh()->amount);
        $this->assertSame(1, $class->feeSchedules()->where('fee_type', 'facility')->count());
        $this->get(route('class-fees.index', ['class_room_id' => $class->id, 'type' => 'facility']))->assertOk()
            ->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 5000.0);
    }

    public function test_unpaid_legacy_exam_setup_can_move_off_its_old_cadence_and_into_the_past(): void
    {
        $class = $this->schoolClass(true);
        $schedule = $class->feeSchedules()->where('fee_type', 'exam')->firstOrFail();
        $this->put(route('classrooms.update', $class), ['name' => $class->name,
            'exam_fee' => 700, 'exam_fee_start_date' => '2026-01-03'])->assertSessionHasNoErrors();
        $this->assertSame('2026-01-03', $schedule->fresh()->first_due_date->toDateString());
        $this->assertSame(700.0, (float) $schedule->fresh()->amount);
        $this->assertSame(1, $class->feeSchedules()->where('fee_type', 'exam')->count());
    }

    public function test_unpaid_terms_can_be_repriced_and_moved_to_an_earlier_year_without_extra_versions(): void
    {
        $class = $this->schoolClass();
        $this->student($class);
        $schedule = $class->feeSchedules()->where('fee_type', 'exam')->firstOrFail();
        $terms = $this->terms(2025);
        $this->put(route('classrooms.update', $class), ['name' => $class->name, 'exam_fee' => 700,
            'exam_fee_start_date' => '2025-01-01', 'exam_terms' => $terms,
            'facility_fee' => 5000, 'facility_fee_start_date' => '2026-01-02'])->assertSessionHasNoErrors();
        $this->assertSame($terms, $schedule->fresh()->terms);
        $this->assertSame(700.0, (float) $schedule->fresh()->amount);
        $this->assertSame(1, $class->feeSchedules()->where('fee_type', 'exam')->count());
        $this->assertSame(2100.0, $this->tracker($class)['due']);
        $this->put(route('classrooms.update', $class), ['name' => $class->name, 'exam_fee' => 900,
            'exam_fee_start_date' => '2028-01-01', 'exam_terms' => $this->terms(2028)])->assertSessionHasNoErrors();
        $this->assertSame(1, $class->feeSchedules()->where('fee_type', 'exam')->count());
        $this->assertSame('2028-01-01', $schedule->fresh()->first_due_date->toDateString());
        $this->assertSame(0.0, $this->tracker($class)['due']);
    }

    public function test_setup_only_versions_can_be_consolidated_or_removed_before_any_payment(): void
    {
        $class = $this->schoolClass();
        $schedule = $class->feeSchedules()->where('fee_type', 'facility')->firstOrFail();
        $schedule->update(['ends_on' => '2026-06-01']);
        $later = $class->feeSchedules()->create(['fee_type' => 'facility', 'amount' => 3000,
            'revenue_category_id' => $schedule->revenue_category_id, 'first_due_date' => '2027-01-15']);
        $this->put(route('classrooms.update', $class), ['name' => $class->name,
            'facility_fee' => 5000, 'facility_fee_start_date' => '2026-01-02'])->assertSessionHasNoErrors();
        $this->assertSame(1, $class->feeSchedules()->where('fee_type', 'facility')->count());
        $this->assertNull($schedule->fresh());
        $this->assertSame('2026-01-02', $later->fresh()->first_due_date->toDateString());
        $this->put(route('classrooms.update', $class), ['name' => $class->name, 'facility_fee' => 0])->assertSessionHasNoErrors();
        $this->assertSame(0, $class->feeSchedules()->where('fee_type', 'facility')->count());
        $this->assertSame(1, $class->feeSchedules()->where('fee_type', 'exam')->count());
        $this->put(route('classrooms.update', $class), ['name' => $class->name,
            'facility_fee' => 4500, 'facility_fee_start_date' => '2026-01-01'])->assertSessionHasNoErrors();
        $this->assertSame(1, $class->feeSchedules()->where('fee_type', 'facility')->count());
    }

    public function test_a_payment_for_one_fee_does_not_lock_an_unpaid_fee_of_the_same_class(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $this->post(route('class-fees.bulk.store'), $this->batch([$this->line($class, $student)]))->assertSessionHasNoErrors();
        $this->put(route('classrooms.update', $class), ['name' => $class->name,
            'facility_fee' => 5000, 'facility_fee_start_date' => '2026-01-02'])->assertSessionHasNoErrors();
        $this->assertSame(1000.0, $this->tracker($class)['paid']);
        $this->assertSame(5000.0, (float) $class->feeSchedules()->where('fee_type', 'facility')->firstOrFail()->amount);
    }

    public function test_historical_and_cancelled_payments_keep_schedule_history_protected(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $line = $this->line($class, $student, '2026-01-15', false, 2000, 'facility');
        $this->post(route('class-fees.bulk.store'), $this->batch([$line]))->assertSessionHasNoErrors();
        $payload = ['name' => $class->name, 'facility_fee' => 5000, 'facility_fee_start_date' => '2026-01-15'];
        $this->put(route('classrooms.update', $class), $payload)->assertSessionHasErrors('facility_fee_start_date');
        $history = ClassFeePaymentHistory::firstOrFail();
        $this->delete(route('class-fees.payments.destroy', ['source' => 'history', 'payment' => $history->id]), ['reason' => 'Incorrect entry'])->assertSessionHasNoErrors();
        $this->put(route('classrooms.update', $class), $payload)->assertSessionHasErrors('facility_fee_start_date');
        $this->assertSame(2000.0, (float) $history->classFeeSchedule->fresh()->amount);
    }

    public function test_invalid_term_dates_still_roll_back_unpaid_setup_changes(): void
    {
        $class = $this->schoolClass();
        $terms = $this->terms();
        $terms[1]['start'] = '2026-04-30';
        $this->put(route('classrooms.update', $class), ['name' => 'Should roll back', 'exam_fee' => 700,
            'exam_fee_start_date' => '2026-01-01', 'exam_terms' => $terms,
            'facility_fee' => 5000, 'facility_fee_start_date' => '2026-01-02'])->assertSessionHasErrors('exam_terms');
        $this->assertNotSame('Should roll back', $class->fresh()->name);
        $this->assertSame(1000.0, (float) $class->feeSchedules()->where('fee_type', 'exam')->firstOrFail()->amount);
        $this->assertSame(2000.0, (float) $class->feeSchedules()->where('fee_type', 'facility')->firstOrFail()->amount);
    }
}
