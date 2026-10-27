<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Revenue;
use App\Models\Student;
use App\Models\User;
use App\Services\Billing\BillNumberService;
use App\Services\Billing\ClassFeeService;
use App\Services\CashBalanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClassFeeComputerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->forgetInstance('settings');
        $this->travelTo(Carbon::parse('2026-10-27 12:00:00'));
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('Admin', 'web'));
        $this->actingAs($user);
    }

    private function terms(int $year = 2026): array
    {
        return [['start' => "$year-01-01", 'end' => "$year-04-30"], ['start' => "$year-05-01", 'end' => "$year-08-31"], ['start' => "$year-09-01", 'end' => "$year-12-31"]];
    }

    private function classroom(bool $computer = true): ClassRoom
    {
        $class = ClassRoom::create(['name' => 'Grade '.Str::random(5), 'active' => true]);
        app(ClassFeeService::class)->configure($class, ['exam_fee' => 700, 'exam_terms' => $this->terms(),
            'computer_fee' => $computer ? 300 : 0, 'facility_fee' => 5000, 'facility_fee_start_date' => '2026-01-08']);

        return $class;
    }

    private function student(ClassRoom $class, string $joined = '2026-01-01'): Student
    {
        return Student::create(['name' => 'Computer pupil '.Str::random(5), 'admission_number' => Str::random(8),
            'class_room_id' => $class->id, 'joining_date' => $joined, 'active' => true, 'alumni' => false]);
    }

    private function cycle(ClassRoom $class, string $type, string $date = '2026-01-01'): string
    {
        return $class->feeSchedules()->where('fee_type', $type)->whereNull('ends_on')->firstOrFail()->id.':'.$date;
    }

    private function payload(ClassRoom $class, Student $student, array $extra = []): array
    {
        return $extra + ['submission_id' => (string) Str::uuid(), 'student_id' => $student->id,
            'cycle' => $this->cycle($class, 'exam'), 'amount' => 700, 'include_computer' => 1,
            'computer_cycle' => $this->cycle($class, 'computer'), 'computer_amount' => 300,
            'paid_at' => '2026-10-27', 'payment_method' => 'cash', 'record_revenue' => 1,
            'return_to' => 'collection', 'view_receipt' => 1];
    }

    private function postFee(ClassRoom $class, array $data, string $type = 'exam')
    {
        return $this->post(route('class-fees.store', ['classroom' => $class, 'type' => $type]), $data);
    }

    private function collection(Student $student, string $type = 'exam')
    {
        return $this->get(route('revenue.items.create', ['quick' => $type, 'student_id' => $student->id, 'year' => 2026]));
    }

    public function test_optional_computer_setup_shares_three_terms_and_classes_without_it_have_no_computer_charges(): void
    {
        $enabled = $this->classroom();
        $disabled = $this->classroom(false);
        $rule = $enabled->feeSchedules()->where('fee_type', 'computer')->firstOrFail();
        $this->assertSame($this->terms(), $rule->terms);
        $this->assertSame(4, $rule->intervalMonths());
        $this->assertSame('Computer Fee', \App\Models\RevenueCategory::where('class_fee_type', 'computer')->firstOrFail()->name);
        $this->assertFalse($disabled->feeSchedules()->where('fee_type', 'computer')->exists());
        $one = $this->student($enabled, '2026-10-01');
        $two = $this->student($disabled);
        $this->collection($one)->assertOk()->assertSee('Include Computer Fee')->assertViewHas('computerEntry', fn ($entry) => $entry['cycle']['term'] === 3 && $entry['status']['collectable'] === 300.0);
        $this->collection($two)->assertOk()->assertDontSee('Include Computer Fee')->assertViewHas('computerEntry', null);
        $this->collection($one, 'computer')->assertOk()->assertSee('Collect Computer Fee')->assertViewHas('installments', fn ($items) => $items->count() === 1 && $items->first()['cycle']['term'] === 3);
        $this->get(route('classrooms.edit', $enabled))->assertOk()->assertSee('Computer fee per term')->assertSee('name="computer_fee"', false);
        $this->post(route('classrooms.store'), ['name' => 'Missing calendar', 'computer_fee' => 300, 'active' => 1])->assertSessionHasErrors('exam_terms');
        $this->post(route('classrooms.store'), ['name' => 'Computer only', 'computer_fee' => 300, 'exam_terms' => $this->terms(), 'active' => 1])->assertSessionHasNoErrors();
        $this->assertSame(3, app(ClassFeeService::class)->cycles(ClassRoom::where('name', 'Computer only')->firstOrFail()->feeSchedules, Carbon::parse('2026-01-01'), Carbon::parse('2026-12-31'))->count());
    }

    public function test_student_grid_groups_matching_term_fees_and_keeps_facility_immediately_before_due(): void
    {
        $class = $this->classroom();
        $student = $this->student($class);
        $this->postFee($class, $this->payload($class, $student, ['include_computer' => 0]))->assertSessionHasNoErrors();
        $filters = ['view' => 'students', 'year' => 2026, 'class_room_id' => $class->id];
        $response = $this->get(route('class-fees.index', $filters))->assertOk()->assertSee('Pay both fees')->assertSee('Payment details / edit');
        $this->assertSame(['exam', 'exam', 'exam', 'computer', 'computer', 'computer', 'facility'], $response->viewData('tracker')['cycles']->pluck('schedule.fee_type')->values()->all());
        $this->assertGroupedGrid($response->getContent(), 3, 7);
        $response->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 7300.0);
        $fragment = $this->getJson(route('class-fees.index', $filters + ['fragment' => 1]))->assertOk();
        $this->assertGroupedGrid($fragment->json('html'), 3, 7);
        $this->get(route('class-fees.index', $filters + ['include_computer' => 0]))->assertOk()->assertDontSee('Pay both fees')
            ->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 6400.0);
    }

    public function test_all_years_grid_keeps_one_student_row_and_annual_fees_in_the_last_fee_column(): void
    {
        $class = $this->classroom();
        app(ClassFeeService::class)->configure($class, ['exam_fee' => 700, 'computer_fee' => 300,
            'exam_terms' => $this->terms(2025), 'facility_fee' => 5000, 'facility_fee_start_date' => '2025-01-08']);
        $this->student($class, '2025-01-01');
        $response = $this->get(route('class-fees.index', ['view' => 'students', 'year' => 2026, 'all_years' => 1, 'class_room_id' => $class->id]))->assertOk();
        $this->assertGroupedGrid($response->getContent(), 6, 14);
        $response->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 16000.0);
        $facility = $this->get(route('class-fees.index', ['view' => 'students', 'type' => 'facility', 'year' => 2026, 'class_room_id' => $class->id]))->assertOk();
        $this->assertGroupedGrid($facility->getContent(), 0, 1);
    }

    private function assertGroupedGrid(string $html, int $termColumns, int $fees): void
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $xpath = new \DOMXPath($dom);
        $table = '//table[@class="fee-register-table"]';
        $headers = $xpath->query($table.'/thead/tr/th');
        $this->assertSame($termColumns + 3, $headers->length);
        $this->assertStringContainsString('Facility Fee', $headers->item($headers->length - 2)->textContent);
        $this->assertStringContainsString('Due now (Rs)', $headers->item($headers->length - 1)->textContent);
        $this->assertSame(1, $xpath->query($table.'/tbody/tr')->length);
        $this->assertSame($fees, $xpath->query($table.'//*[@data-fee-cycle]')->length);
        foreach ($xpath->query($table.'//a[@data-register-action]') as $action) {
            parse_str(parse_url($action->getAttribute('href'), PHP_URL_QUERY), $query);
            $fee = $action->parentNode;
            while ($fee && ! $fee->hasAttribute('data-fee-cycle')) {
                $fee = $fee->parentNode;
            }
            $this->assertSame($fee->getAttribute('data-fee-cycle'), $query['cycle']);
        }
    }

    public function test_combined_cash_uses_one_bill_with_two_separate_balances_and_retries_are_idempotent(): void
    {
        $class = $this->classroom();
        $student = $this->student($class);
        $data = $this->payload($class, $student);
        $response = $this->postFee($class, $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('revenues', 2);
        $exam = Revenue::whereHas('category', fn ($q) => $q->where('class_fee_type', 'exam'))->firstOrFail();
        $computer = Revenue::whereHas('category', fn ($q) => $q->where('class_fee_type', 'computer'))->firstOrFail();
        $this->assertSame($exam->bill_no.'-C', $computer->bill_no);
        $this->assertSame('BILL-1001', app(BillNumberService::class)->peekNextRevenueBillNumber());
        $response->assertRedirect(route('revenue.items.receipt', $exam));
        foreach ([$exam, $computer] as $receipt) {
            $this->get(route('revenue.items.receipt', $receipt))->assertOk()->assertViewIs('class-fees.combined-receipt')->assertSee('Exam Fee')->assertSee('Computer Fee')->assertSee('Term 1 (2026)')->assertSee('data-receipt-total="1000.00"', false);
        }
        $this->assertSame(1000.0, app(CashBalanceService::class)->balanceOn('2026-10-27'));
        $this->collection($student)->assertViewHas('computerEntry', null);
        $this->postFee($class, $data)->assertSessionHasNoErrors()->assertRedirect(route('revenue.items.receipt', $exam));
        $this->assertDatabaseCount('revenues', 2);
        $this->postFee($class, array_replace($data, ['computer_amount' => 250]))->assertSessionHasErrors('submission_id');
        $this->assertDatabaseCount('revenues', 2);
        $this->get(route('reports.daily_ledger', ['from' => '2026-10-27', 'to' => '2026-10-27']))->assertOk()->assertViewHas('totalIn', 1000.0)->assertSee('Computer Fee');
    }

    public function test_combined_bill_uses_the_established_school_receipt_with_one_total_and_amount_in_words(): void
    {
        $class = $this->classroom();
        app(ClassFeeService::class)->configure($class, ['exam_fee' => 5000, 'computer_fee' => 1500, 'exam_terms' => $this->terms()]);
        $student = $this->student($class);
        $student->update(['guardian_name' => 'Nalin Preview', 'address' => '12 Sample Road, Polgahawela']);
        $this->postFee($class, $this->payload($class, $student, ['amount' => 5000, 'computer_amount' => 1500,
            'cycle' => $this->cycle($class, 'exam', '2026-05-01'), 'computer_cycle' => $this->cycle($class, 'computer', '2026-05-01')]))->assertSessionHasNoErrors();
        $printed = [];
        foreach (Revenue::orderBy('id')->get() as $payment) {
            $response = $this->get(route('revenue.items.receipt', $payment))->assertOk()
                ->assertSee('POLGAHAWELA BRITISH COLLEGE')->assertSee('Received with thanks from Master/Ms')
                ->assertSee('Nalin Preview')->assertSee('12 Sample Road, Polgahawela')
                ->assertSee('Six Thousand Five Hundred Rupees Only')->assertSee('Term 2 (2026)')
                ->assertSee('Authorized Signature | Cashier')->assertSee('data-receipt-total="6500.00"', false)
                ->assertDontSee('Accounting reference:');
            $dom = new \DOMDocument;
            @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
            $xpath = new \DOMXPath($dom);
            $this->assertSame(1, $xpath->query('//*[@id="receipt-print"]')->length);
            $this->assertSame(2, $xpath->query('//*[@data-receipt-fee]')->length);
            $this->assertStringContainsString('5,000.00', $xpath->query('//*[@data-receipt-fee="exam"]')->item(0)->textContent);
            $this->assertStringContainsString('1,500.00', $xpath->query('//*[@data-receipt-fee="computer"]')->item(0)->textContent);
            $printed[] = preg_replace('/\s+/u', ' ', $xpath->query('//*[@id="receipt-print"]')->item(0)->textContent);
        }
        $this->assertSame($printed[0], $printed[1], 'Either accounting component must open the same school bill.');
        $this->assertSame(6500.0, app(CashBalanceService::class)->balanceOn('2026-10-27'));
    }

    public function test_combined_school_receipt_keeps_refunds_corrected_dates_and_returned_cheques_clear(): void
    {
        $class = $this->classroom();
        app(ClassFeeService::class)->configure($class, ['exam_fee' => 700.25, 'computer_fee' => 300.50, 'exam_terms' => $this->terms()]);
        $student = $this->student($class);
        $this->postFee($class, $this->payload($class, $student, ['amount' => 700.25, 'computer_amount' => 300.50]))->assertSessionHasNoErrors();
        $exam = Revenue::orderBy('id')->firstOrFail();
        $computer = Revenue::orderByDesc('id')->firstOrFail();
        $computer->update(['paid_at' => '2026-10-26']);
        $exam->adjustments()->create(['student_id' => $student->id, 'type' => 'refund', 'amount' => 100]);
        $this->get(route('revenue.items.receipt', $computer))->assertOk()->assertSee('data-receipt-total="1000.75"', false)
            ->assertSee('One Thousand Rupees And Seventy Five Cents Only')
            ->assertSee('Refunded :')->assertSee('900.75')->assertSee('Payment date: 26/10/2026');
        $other = $this->student($class);
        $this->postFee($class, $this->payload($class, $other, ['payment_method' => 'cheque', 'cheque_date' => '2026-10-27', 'cheque_number' => 'CH102', 'cheque_bank' => 'Preview Bank']))->assertSessionHasNoErrors();
        $cheque = Revenue::where('student_id', $other->id)->firstOrFail();
        $this->get(route('revenue.items.receipt', $cheque))->assertOk()->assertSee('CHEQUE PENDING')->assertSee('Net Collected :');
        $this->post(route('revenue.items.cheque.returned', $cheque))->assertSessionHasNoErrors();
        $this->get(route('revenue.items.receipt', $cheque))->assertOk()->assertSee('CHEQUE RETURNED')
            ->assertSee('Cheque returned')->assertSee('data-receipt-total="0.00"', false)->assertSee('Zero Rupees Only');
    }

    public function test_combined_payment_is_atomic_and_cannot_pay_a_different_term_or_overpay_the_computer_fee(): void
    {
        $class = $this->classroom();
        $student = $this->student($class);
        $data = $this->payload($class, $student, ['computer_cycle' => $this->cycle($class, 'computer', '2026-05-01')]);
        $this->postFee($class, $data)->assertSessionHasErrors('computer_cycle');
        $this->postFee($class, array_replace($data, ['computer_cycle' => $this->cycle($class, 'computer'), 'computer_amount' => 301]))->assertSessionHasErrors('computer_amount');
        $this->assertDatabaseCount('revenues', 0);
        $this->assertDatabaseCount('class_fee_batches', 0);
        $this->assertSame('BILL-1000', app(BillNumberService::class)->peekNextRevenueBillNumber());
        $this->postFee($class, $this->payload($class, $student, ['computer_cycle' => null]))->assertSessionHasErrors('computer_cycle');
        $this->postFee($class, $this->payload($class, $student, ['return_to' => null, 'record_revenue' => 0]))->assertSessionHasErrors('include_computer');
        $without = $this->classroom(false);
        $outsider = $this->student($without);
        $this->postFee($without, array_replace($data, ['student_id' => $outsider->id, 'cycle' => $this->cycle($without, 'exam')]))->assertSessionHasErrors('computer_cycle');
        $this->assertDatabaseCount('revenues', 0);
    }

    public function test_exam_or_computer_can_be_paid_separately_with_partial_amounts_and_correct_category(): void
    {
        $class = $this->classroom();
        $student = $this->student($class);
        $this->postFee($class, $this->payload($class, $student, ['include_computer' => 0, 'amount' => 200]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('revenues', 1);
        $this->collection($student)->assertViewHas('selectedStatus', fn ($status) => $status['collectable'] === 500.0)->assertViewHas('computerEntry', fn ($entry) => $entry['status']['collectable'] === 300.0);
        $computerData = $this->payload($class, $student, ['include_computer' => 0, 'cycle' => $this->cycle($class, 'computer'), 'amount' => 150]);
        $this->postFee($class, $computerData, 'computer')->assertSessionHasNoErrors();
        $payment = Revenue::where('class_fee_schedule_id', explode(':', $computerData['cycle'])[0])->firstOrFail();
        $this->get(route('revenue.items.receipt', $payment))->assertOk()->assertSee('Computer Fee')->assertSee('Term 1 (2026)')->assertDontSee('Monthly fee :');
        $this->assertSame(350.0, app(CashBalanceService::class)->balanceOn('2026-10-27'));
        $this->collection($student, 'computer')->assertViewHas('selectedStatus', fn ($status) => $status['collectable'] === 150.0);
    }

    public function test_combined_cheque_stays_pending_and_passing_either_line_clears_both_at_the_selected_date(): void
    {
        $class = $this->classroom();
        $student = $this->student($class);
        $data = $this->payload($class, $student, ['payment_method' => 'cheque', 'cheque_date' => '2026-10-28', 'cheque_number' => 'CH100', 'cheque_bank' => 'Test Bank']);
        $this->postFee($class, $data)->assertSessionHasNoErrors();
        $items = Revenue::orderBy('id')->get();
        $this->assertSame(['hold', 'hold'], $items->pluck('payment_status')->all());
        $this->assertSame(0.0, app(CashBalanceService::class)->balanceOn('2026-10-27'));
        $this->get(route('revenue.items.receipt', $items->last()))->assertOk()->assertSee('CHEQUE PENDING')->assertSee('data-receipt-total="1000.00"', false);
        $this->post(route('revenue.items.cheque.passed', $items->first()), ['passed_date' => '2026-10-27'])->assertSessionHasErrors('passed_date');
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00'));
        $this->post(route('revenue.items.cheque.passed', $items->last()), ['passed_date' => '2026-10-28'])->assertSessionHasNoErrors();
        $this->assertSame(['confirmed', 'confirmed'], Revenue::orderBy('id')->pluck('payment_status')->all());
        $this->assertSame(['2026-10-28', '2026-10-28'], Revenue::all()->map(fn ($r) => $r->confirmed_at->toDateString())->all());
        $this->get(route('reports.daily_ledger', ['from' => '2026-10-28', 'to' => '2026-10-28']))->assertOk()->assertViewHas('totalIn', 1000.0);
        $this->post(route('revenue.items.cheque.passed', $items->first()))->assertSessionHasErrors('cheque');
    }

    public function test_returned_combined_cheque_reopens_both_balances_and_cashier_permissions_protect_the_bill(): void
    {
        $class = $this->classroom();
        $student = $this->student($class);
        $data = $this->payload($class, $student, ['payment_method' => 'cheque', 'cheque_date' => '2026-10-27', 'cheque_number' => 'CH101', 'cheque_bank' => 'Bank']);
        $this->postFee($class, $data)->assertSessionHasNoErrors();
        $items = Revenue::orderBy('id')->get();
        $this->post(route('revenue.items.cheque.returned', $items->last()))->assertSessionHasNoErrors();
        $this->assertSame(['rejected', 'rejected'], Revenue::orderBy('id')->pluck('payment_status')->all());
        $this->collection($student)->assertViewHas('selectedStatus', fn ($status) => $status['collectable'] === 700.0)->assertViewHas('computerEntry', fn ($entry) => $entry['status']['collectable'] === 300.0);
        $user = User::factory()->create();
        $user->givePermissionTo('revenue.add');
        $this->actingAs($user)->get(route('revenue.items.receipt', $items->first()))->assertForbidden();
        $this->actingAs($user)->get(route('revenue.items.receipt', $items->last()))->assertForbidden();
    }

    public function test_correcting_and_cancelling_a_computer_component_keeps_the_exam_payment_and_updates_the_bill(): void
    {
        $class = $this->classroom();
        $student = $this->student($class);
        $this->postFee($class, $this->payload($class, $student))->assertSessionHasNoErrors();
        $exam = Revenue::orderBy('id')->firstOrFail();
        $computer = Revenue::orderByDesc('id')->firstOrFail();
        $this->put(route('class-fees.payments.update', ['source' => 'revenue', 'payment' => $computer->id]), ['amount' => 250, 'paid_at' => '2026-10-27', 'record_revenue' => 1])->assertSessionHasNoErrors();
        $this->get(route('revenue.items.receipt', $exam))->assertOk()->assertSee('data-receipt-total="950.00"', false);
        $this->delete(route('class-fees.payments.destroy', ['source' => 'revenue', 'payment' => $computer->id]), ['reason' => 'Computer fee entered incorrectly'])->assertSessionHasNoErrors();
        $this->assertFalse($exam->fresh()->isCancelled());
        $this->get(route('revenue.items.receipt', $exam))->assertOk()->assertSee('data-receipt-total="700.00"', false)->assertSee('Computer fee entered incorrectly');
        $this->collection($student, 'computer')->assertViewHas('selectedStatus', fn ($status) => $status['collectable'] === 300.0);
        $this->assertSame(700.0, app(CashBalanceService::class)->balanceOn('2026-10-27'));
    }

    public function test_unpaid_computer_setup_can_change_but_paid_rates_require_a_future_year_and_preserve_exam_receipts(): void
    {
        $class = $this->classroom(false);
        $student = $this->student($class);
        $data = ['submission_id' => (string) Str::uuid(), 'student_id' => $student->id, 'cycle' => $this->cycle($class, 'exam'), 'amount' => 700, 'paid_at' => '2026-10-27'];
        $this->postFee($class, $data)->assertSessionHasNoErrors();
        $exam = Revenue::firstOrFail();
        app(ClassFeeService::class)->configure($class, ['computer_fee' => 300]);
        $this->assertSame('700.00', $exam->fresh()->amount);
        app(ClassFeeService::class)->configure($class, ['computer_fee' => 350]);
        $this->assertSame('350.00', $class->feeSchedules()->where('fee_type', 'computer')->first()->amount);
        $this->postFee($class, $this->payload($class, $student, ['include_computer' => 0, 'cycle' => $this->cycle($class, 'computer'), 'amount' => 350]), 'computer')->assertSessionHasNoErrors();
        $this->put(route('classrooms.update', $class), ['name' => $class->name, 'exam_fee' => 700, 'exam_fee_start_date' => '2026-01-01', 'exam_terms' => $this->terms(), 'computer_fee' => 400])->assertSessionHasErrors('computer_fee');
        $this->assertSame('350.00', $class->feeSchedules()->where('fee_type', 'computer')->first()->amount);
        app(ClassFeeService::class)->configure($class, ['computer_fee' => 400, 'exam_terms' => $this->terms(2027)]);
        $this->assertSame(2, $class->feeSchedules()->where('fee_type', 'computer')->count());
        $this->assertSame('2026-01-01', $exam->fresh()->class_fee_due_date->toDateString());
        $this->assertSame('350.00', Revenue::whereHas('category', fn ($q) => $q->where('class_fee_type', 'computer'))->first()->amount);
    }

    public function test_register_term_filters_bulk_history_and_exports_include_optional_computer_fees(): void
    {
        $class = $this->classroom();
        $student = $this->student($class);
        $filters = ['view' => 'students', 'year' => 2026, 'class_room_id' => $class->id, 'include_facility' => 0, 'exam_selection' => 1, 'exam_dates' => ['2026-05-01']];
        $this->get(route('class-fees.index', $filters))->assertOk()->assertSee('Include computer fee')
            ->assertViewHas('tracker', fn ($tracker) => $tracker['cycles']->count() === 2 && $tracker['due'] === 1000.0);
        $this->get(route('class-fees.index', $filters + ['include_computer' => 0]))->assertOk()
            ->assertViewHas('computerOptionsAvailable', true)->assertSee('Include computer fee')
            ->assertViewHas('tracker', fn ($tracker) => $tracker['cycles']->count() === 1 && $tracker['due'] === 700.0);
        $this->get(route('class-fees.index', ['view' => 'students', 'year' => 2026, 'type' => 'computer', 'class_room_id' => $class->id]))->assertOk()->assertViewHas('tracker', fn ($tracker) => $tracker['cycles']->count() === 3 && $tracker['due'] === 900.0);
        $bulk = $this->get(route('class-fees.bulk', ['year' => 2026, 'class_room_id' => $class->id]))->assertOk()->assertSee('Computer fee only');
        $this->assertSame(3, collect($bulk->viewData('candidates')->first()['installments'])->where('type', 'computer')->count());
        $this->post(route('class-fees.bulk.store'), ['submission_id' => (string) Str::uuid(), 'year' => 2026, 'paid_at' => '2026-10-27', 'payment_method' => 'cash',
            'lines' => [['student_id' => $student->id, 'class_room_id' => $class->id, 'cycle' => $this->cycle($class, 'computer'), 'amount' => 300, 'record_revenue' => 0]]])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('class_fee_payment_histories', 1);
        $this->assertDatabaseCount('revenues', 0);
        $this->get(route('class-fees.bulk', ['year' => 2026, 'mode' => 'unpaid']))->assertOk()->assertViewHas('candidates', fn ($candidates) => collect($candidates->first()['installments'])->first()['type'] === 'computer');
        $this->get(route('reports.overview.details', ['metric' => 'computer']))->assertOk()->assertViewHas('filteredTotal', 600.0);
        $this->get(route('dashboard'))->assertOk()->assertViewHas('classFeeSummaries', fn ($summaries) => $summaries['computer']['due'] === 600.0);
        $pdf = $this->get(route('class-fees.index', ['type' => 'computer', 'year' => 2026, 'class_room_id' => $class->id, 'pdf' => 1]))->assertOk()->assertDownload();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $csv = $this->get(route('class-fees.index', $filters + ['download' => 'csv']))->assertOk()->assertDownload();
        ob_start();
        $csv->baseResponse->sendContent();
        $text = ob_get_clean();
        $this->assertStringContainsString('Computer Fee', $text);
        $this->assertStringContainsString('2026-05-01', $text);
    }

    public function test_bulk_pairs_same_student_and_term_on_one_bill_and_keeps_history_only_entries_separate(): void
    {
        $class = $this->classroom();
        $first = $this->student($class);
        $second = $this->student($class);
        $lines = [];
        foreach ([$first, $second] as $student) {
            foreach (['computer' => 300, 'exam' => 700] as $type => $amount) {
                $lines[] = ['student_id' => $student->id, 'class_room_id' => $class->id,
                    'cycle' => $this->cycle($class, $type), 'amount' => $amount, 'record_revenue' => $student->id === $first->id ? 1 : 0];
            }
        }
        $data = ['submission_id' => (string) Str::uuid(), 'year' => 2026, 'paid_at' => '2026-10-27',
            'payment_method' => 'bank_transfer', 'bank_name' => 'Test Bank', 'lines' => $lines];
        $this->post(route('class-fees.bulk.store'), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('revenues', 2);
        $this->assertDatabaseCount('class_fee_payment_histories', 2);
        $this->assertSame(1, Revenue::all()->pluck('payment_meta.fee_bundle.id')->unique()->count());
        $this->get(route('revenue.items.receipt', Revenue::firstOrFail()))->assertOk()->assertSee('data-receipt-total="1000.00"', false)->assertSee('Test Bank');
        $this->assertSame('BILL-1001', app(BillNumberService::class)->peekNextRevenueBillNumber());
        $this->post(route('class-fees.bulk.store'), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('revenues', 2);
        $this->assertDatabaseCount('class_fee_payment_histories', 2);
        $this->assertSame(0.0, app(CashBalanceService::class)->balanceOn('2026-10-27'));
    }

    public function test_computer_setup_rejects_a_calendar_different_from_the_exam_terms(): void
    {
        $class = $this->classroom();
        $terms = $this->terms();
        $terms[0]['end'] = '2026-04-29';
        $this->put(route('classrooms.update', $class), ['name' => $class->name, 'computer_fee' => 300, 'exam_terms' => $terms])->assertSessionHasErrors('computer_fee');
        $this->assertSame($this->terms(), $class->feeSchedules()->where('fee_type', 'computer')->firstOrFail()->terms);
    }
}
