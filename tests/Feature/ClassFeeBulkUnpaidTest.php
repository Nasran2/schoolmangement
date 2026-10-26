<?php

namespace Tests\Feature;

use App\Models\AuditLog;
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

class ClassFeeBulkUnpaidTest extends TestCase
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
        $class = ClassRoom::create(['name' => 'Class '.Str::random(6), 'active' => true]);
        app(ClassFeeService::class)->configure($class, ['exam_fee' => 1000, 'exam_terms' => [
            ['start' => '2026-01-01', 'end' => '2026-04-30'], ['start' => '2026-05-01', 'end' => '2026-08-31'],
            ['start' => '2026-09-01', 'end' => '2026-12-31'],
        ], 'facility_fee' => 2000, 'facility_fee_start_date' => '2026-01-15']);

        return $class;
    }

    private function student(ClassRoom $class, array $values = []): Student
    {
        return Student::create($values + ['name' => 'Student '.Str::random(6), 'admission_number' => Str::random(8),
            'class_room_id' => $class->id, 'joining_date' => '2025-01-01', 'active' => true, 'alumni' => false]);
    }

    private function pay(ClassRoom $class, Student $student, string $date = '2026-01-01', array $extra = [], string $type = 'exam'): Revenue|ClassFeePaymentHistory
    {
        $rule = $class->feeSchedules()->where('fee_type', $type)->firstOrFail();

        return app(ClassFeePaymentService::class)->collect($class, $type, $extra + [
            'student_id' => $student->id, 'cycle' => $rule->id.':'.$date, 'submission_id' => (string) Str::uuid(),
            'amount' => $type === 'facility' ? 2000 : 1000, 'paid_at' => '2026-10-26', 'payment_method' => 'cash',
        ], auth()->user());
    }

    private function page(array $filters = [])
    {
        return $this->get(route('class-fees.bulk', $filters + ['mode' => 'unpaid', 'year' => 2026]))->assertOk();
    }

    private function line(ClassRoom $class, Student $student, string $date = '2026-01-01'): array
    {
        $candidate = $this->page(['include_inactive' => 1])->viewData('candidates')->first(fn ($candidate) => $candidate['student_id'] === $student->id && $candidate['class_id'] === $class->id);
        $cycle = collect($candidate['installments'])->first(fn ($cycle) => str_ends_with($cycle['key'], ':'.$date));
        $this->assertNotNull($cycle);

        return ['student_id' => $student->id, 'class_room_id' => $class->id, 'cycle' => $cycle['key'], 'fingerprint' => $cycle['fingerprint']];
    }

    private function batch(array $lines, array $extra = []): array
    {
        return $extra + ['submission_id' => (string) Str::uuid(), 'year' => 2026, 'reason' => 'Wrong students marked paid', 'lines' => $lines];
    }

    public function test_bulk_unpaid_cancels_revenue_and_history_across_classes_terms_and_facility_without_losing_bills(): void
    {
        $first = $this->school();
        $alice = $this->student($first);
        $second = $this->school();
        $bob = $this->student($second);
        $receipt = $this->pay($first, $alice);
        $bill = $receipt->bill_no;
        $history = $this->pay($first, $alice, '2026-05-01', ['record_revenue' => 0]);
        $facility = $this->pay($second, $bob, '2026-01-15', ['payment_method' => 'bank_transfer', 'bank_name' => 'Test bank'], 'facility');
        $unselected = $this->pay($second, $bob, '2026-09-01');
        $data = $this->batch([$this->line($first, $alice), $this->line($first, $alice, '2026-05-01'), $this->line($second, $bob, '2026-01-15')]);
        $this->post(route('class-fees.bulk.unpaid'), $data)->assertSessionHasNoErrors()->assertRedirect(route('class-fees.bulk', ['year' => 2026, 'mode' => 'unpaid']));
        foreach ([$receipt, $history, $facility] as $payment) {
            $this->assertTrue($payment->fresh()->isCancelled());
        }
        $this->assertFalse($unselected->fresh()->isCancelled());
        $this->assertSame($bill, $receipt->fresh()->bill_no);
        $this->assertSame('Wrong students marked paid', $receipt->fresh()->cancel_reason);
        $this->assertDatabaseCount('revenues', 3);
        $this->assertDatabaseCount('class_fee_payment_histories', 1);
        $this->assertDatabaseCount('class_fee_batches', 1);
        $this->assertSame(1000.0, app(CashBalanceService::class)->balanceOn('2026-10-26'));
        $this->assertSame(3, AuditLog::where('action', 'class_fee.payment_deleted')->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'class_fee.bulk_made_unpaid', 'description' => $data['reason']]);
        $this->get(route('class-fees.index', ['year' => 2026]))->assertOk()
            ->assertViewHas('dueBreakdown', fn ($cards) => $cards['term_1']['due'] === 2000.0 && $cards['term_2']['due'] === 2000.0 && $cards['facility']['due'] === 4000.0);
        $this->get(route('reports.daily_ledger', ['from' => '2026-10-26']))->assertOk()->assertViewHas('totalIn', 1000.0)->assertSee($bill)->assertSee($data['reason']);
    }

    public function test_partial_fee_with_multiple_receipts_reopens_the_full_fee_and_cancels_a_pending_hold_on_it(): void
    {
        $class = $this->school();
        $student = $this->student($class);
        $cash = $this->pay($class, $student, extra: ['amount' => 400]);
        $history = $this->pay($class, $student, extra: ['amount' => 300, 'record_revenue' => 0]);
        $hold = $this->pay($class, $student, extra: ['amount' => 300, 'payment_method' => 'cheque', 'cheque_bank' => 'Bank', 'cheque_number' => '123', 'cheque_date' => '2026-10-27']);
        $page = $this->page();
        $cycle = $page->viewData('candidates')->first()['installments'][0];
        $this->assertSame(700.0, $cycle['paid']);
        $this->assertSame(400.0, $cycle['revenue']);
        $this->assertSame(300.0, $cycle['pending']);
        $this->assertCount(3, $cycle['payments']);
        $this->post(route('class-fees.bulk.unpaid'), $this->batch([$this->line($class, $student)]))->assertSessionHasNoErrors();
        foreach ([$cash, $history, $hold] as $payment) {
            $this->assertTrue($payment->fresh()->isCancelled());
        }
        $this->get(route('class-fees.index', ['class_room_id' => $class->id, 'year' => 2026, 'include_facility' => 0]))->assertOk()
            ->assertViewHas('tracker', fn ($tracker) => $tracker['paid'] === 0.0 && $tracker['due'] === 3000.0 && $tracker['rows']->sum('pending') === 0.0);
    }

    public function test_a_changed_receipt_rolls_back_the_whole_batch_including_audits_and_batch_token(): void
    {
        $class = $this->school();
        $first = $this->student($class);
        $second = $this->student($class);
        $one = $this->pay($class, $first);
        $two = $this->pay($class, $second);
        $data = $this->batch([$this->line($class, $first), $this->line($class, $second)]);
        $two->update(['amount' => 900]);
        $this->post(route('class-fees.bulk.unpaid'), $data)->assertSessionHasErrors('lines.1');
        $this->assertFalse($one->fresh()->isCancelled());
        $this->assertFalse($two->fresh()->isCancelled());
        $this->assertSame(1000.0, (float) $one->fresh()->amount);
        $this->assertDatabaseCount('class_fee_batches', 0);
        $this->assertSame(0, AuditLog::where('action', 'class_fee.payment_deleted')->count());
    }

    public function test_new_receipts_are_not_silently_added_to_a_previously_reviewed_reversal(): void
    {
        $class = $this->school();
        $student = $this->student($class);
        $first = $this->pay($class, $student, extra: ['amount' => 400]);
        $data = $this->batch([$this->line($class, $student)]);
        $second = $this->pay($class, $student, extra: ['amount' => 600]);
        $this->post(route('class-fees.bulk.unpaid'), $data)->assertSessionHasErrors('lines.0');
        $this->assertFalse($first->fresh()->isCancelled());
        $this->assertFalse($second->fresh()->isCancelled());
    }

    public function test_retries_are_idempotent_even_after_the_fee_is_paid_again_and_changed_payloads_are_rejected(): void
    {
        $class = $this->school();
        $student = $this->student($class);
        $this->pay($class, $student);
        $data = $this->batch([$this->line($class, $student)]);
        $this->post(route('class-fees.bulk.unpaid'), $data)->assertSessionHasNoErrors();
        $new = $this->pay($class, $student);
        $this->post(route('class-fees.bulk.unpaid'), $data)->assertSessionHasNoErrors();
        $this->assertFalse($new->fresh()->isCancelled());
        $this->assertSame(1, AuditLog::where('action', 'class_fee.bulk_made_unpaid')->count());
        $this->post(route('class-fees.bulk.unpaid'), array_replace($data, ['reason' => 'Changed reason']))->assertSessionHasErrors('submission_id');
        $otherAdmin = User::factory()->create();
        $otherAdmin->assignRole(Role::findOrCreate('Admin', 'web'));
        $this->actingAs($otherAdmin)->post(route('class-fees.bulk.unpaid'), $data)->assertSessionHasErrors('submission_id');
        $this->assertFalse($new->fresh()->isCancelled());
    }

    public function test_adjusted_receipts_are_flagged_and_cannot_be_reversed_by_forging_a_selection(): void
    {
        $class = $this->school();
        $student = $this->student($class);
        $payment = $this->pay($class, $student);
        $payment->adjustments()->create(['student_id' => $student->id, 'type' => 'refund', 'amount' => 100, 'reason' => 'Partial refund']);
        $candidate = $this->page()->viewData('candidates')->first();
        $this->assertStringContainsString('refunds or waivers', $candidate['installments'][0]['blocked_reason']);
        $this->post(route('class-fees.bulk.unpaid'), $this->batch([$this->line($class, $student)]))->assertSessionHasErrors('lines.0');
        $this->assertFalse($payment->fresh()->isCancelled());
        $this->assertDatabaseCount('class_fee_batches', 0);
    }

    public function test_year_and_class_and_student_are_checked_on_the_server(): void
    {
        $class = $this->school();
        $other = $this->school();
        $student = $this->student($class);
        $otherStudent = $this->student($other);
        $payment = $this->pay($class, $student);
        $line = $this->line($class, $student);
        foreach ([['year' => 2025], ['lines' => [array_replace($line, ['class_room_id' => $other->id])]],
            ['lines' => [array_replace($line, ['student_id' => $otherStudent->id])]],
            ['lines' => [array_replace($line, ['cycle' => explode(':', $line['cycle'])[0].':2026-99-99'])]]] as $extra) {
            $this->post(route('class-fees.bulk.unpaid'), $this->batch([$line], $extra))->assertSessionHasErrors('lines.0');
        }
        $this->assertFalse($payment->fresh()->isCancelled());
        $this->assertDatabaseCount('class_fee_batches', 0);
    }

    public function test_delete_permission_controls_mode_and_submissions_independently_of_collection_permission(): void
    {
        $class = $this->school();
        $student = $this->student($class);
        $this->pay($class, $student);
        $data = $this->batch([$this->line($class, $student)]);
        $cashier = User::factory()->create();
        $cashier->givePermissionTo('revenue.add');
        $this->actingAs($cashier);
        $this->get(route('class-fees.bulk'))->assertOk()->assertDontSee('>Make unpaid</a>', false);
        $this->get(route('class-fees.bulk', ['mode' => 'unpaid']))->assertForbidden();
        $this->post(route('class-fees.bulk.unpaid'), $data)->assertForbidden();
        $deleter = User::factory()->create();
        $deleter->givePermissionTo('revenue.delete');
        $this->actingAs($deleter);
        $this->page()->assertSee('Reason for making these fees unpaid')->assertDontSee('>Collect payments</a>', false);
        $this->get(route('class-fees.bulk'))->assertForbidden();
        $this->post(route('class-fees.bulk.store'), [])->assertForbidden();
        $this->post(route('class-fees.bulk.unpaid'), $data)->assertSessionHasNoErrors();
    }

    public function test_paid_fees_are_available_by_original_class_and_inactive_students_require_the_filter(): void
    {
        $class = $this->school();
        $student = $this->student($class);
        $this->pay($class, $student);
        $destination = $this->school();
        $move = StudentPromotionHistory::create(['student_id' => $student->id, 'from_class_room_id' => $class->id,
            'to_class_room_id' => $destination->id, 'action' => 'transfer']);
        $move->forceFill(['created_at' => Carbon::parse('2026-06-01')])->save();
        $student->update(['class_room_id' => $destination->id, 'active' => false]);
        $this->assertCount(0, $this->page()->viewData('candidates'));
        $page = $this->page(['include_inactive' => 1, 'class_room_id' => $class->id]);
        $candidate = $page->viewData('candidates')->first();
        $this->assertSame($class->id, $candidate['class_id']);
        $this->assertSame($student->id, $candidate['student_id']);
        $this->post(route('class-fees.bulk.unpaid'), $this->batch([$this->line($class, $student)]))->assertSessionHasNoErrors();
    }

    public static function invalidInputs(): array
    {
        return [['reason', ''], ['reason', 'xx'], ['reason', str_repeat('a', 1001)], ['submission_id', 'bad'],
            ['year', 2100], ['lines', []], ['lines_payload', '{bad']];
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_or_missing_reversal_details_do_not_change_payments(string $field, mixed $value): void
    {
        $class = $this->school();
        $student = $this->student($class);
        $payment = $this->pay($class, $student);
        $data = $this->batch([$this->line($class, $student)], [$field => $value]);
        $this->post(route('class-fees.bulk.unpaid'), $data)->assertSessionHasErrors($field === 'lines_payload' ? 'lines' : $field);
        $this->assertFalse($payment->fresh()->isCancelled());
        $this->assertDatabaseCount('class_fee_batches', 0);
    }

    public function test_recorded_payments_remain_correctable_when_an_older_class_move_has_no_history(): void
    {
        $original = $this->school();
        $destination = $this->school();
        $student = $this->student($original);
        $payment = $this->pay($original, $student);
        $student->update(['class_room_id' => $destination->id]);
        $line = $this->line($original, $student);
        $this->post(route('class-fees.bulk.unpaid'), $this->batch([$line]))->assertSessionHasNoErrors();
        $this->assertTrue($payment->fresh()->isCancelled());
    }

    public function test_oversized_batches_and_collection_batch_tokens_cannot_cancel_a_payment(): void
    {
        $class = $this->school();
        $student = $this->student($class);
        $token = (string) Str::uuid();
        $rule = $class->feeSchedules()->where('fee_type', 'exam')->firstOrFail();
        $this->post(route('class-fees.bulk.store'), ['submission_id' => $token, 'year' => 2026,
            'paid_at' => '2026-10-26', 'payment_method' => 'cash', 'lines' => [
                ['student_id' => $student->id, 'class_room_id' => $class->id, 'cycle' => $rule->id.':2026-01-01', 'amount' => 1000, 'record_revenue' => 1],
            ]])->assertSessionHasNoErrors();
        $payment = Revenue::firstOrFail();
        $line = $this->line($class, $student);
        $this->post(route('class-fees.bulk.unpaid'), $this->batch(array_fill(0, 501, $line)))->assertSessionHasErrors('lines');
        $this->post(route('class-fees.bulk.unpaid'), $this->batch([$line], ['submission_id' => $token]))->assertSessionHasErrors('submission_id');
        $this->assertFalse($payment->fresh()->isCancelled());
        $this->assertDatabaseCount('class_fee_batches', 1);
    }

    public function test_duplicate_terms_are_rejected_and_json_submission_works(): void
    {
        $class = $this->school();
        $student = $this->student($class);
        $payment = $this->pay($class, $student);
        $line = $this->line($class, $student);
        $this->post(route('class-fees.bulk.unpaid'), $this->batch([$line, $line]))->assertSessionHasErrors('lines');
        $data = $this->batch([$line]);
        $data['lines_payload'] = json_encode($data['lines']);
        unset($data['lines']);
        $this->post(route('class-fees.bulk.unpaid'), $data)->assertSessionHasNoErrors();
        $this->assertTrue($payment->fresh()->isCancelled());
    }

    public function test_empty_paid_list_and_prepaid_future_terms_are_supported(): void
    {
        $this->assertCount(0, $this->page()->viewData('candidates'));
        $class = $this->school();
        $student = $this->student($class);
        $this->travelTo(Carbon::parse('2026-02-01'));
        $payment = $this->pay($class, $student, '2026-09-01', ['paid_at' => '2026-02-01']);
        $this->post(route('class-fees.bulk.unpaid'), $this->batch([$this->line($class, $student, '2026-09-01')]))->assertSessionHasNoErrors();
        $this->assertTrue($payment->fresh()->isCancelled());
        $this->assertCount(0, $this->page()->viewData('candidates'));
    }
}
