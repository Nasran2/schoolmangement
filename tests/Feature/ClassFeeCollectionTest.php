<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentPromotionHistory;
use App\Models\User;
use App\Services\Billing\ClassFeeService;
use App\Services\CashBalanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClassFeeCollectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->forgetInstance('settings');
        $this->travelTo(Carbon::parse('2026-10-23 12:00:00'));
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('Admin', 'web'));
        $this->actingAs($user);
    }

    private function schoolClass(string $name = 'Grade 2'): ClassRoom
    {
        $class = ClassRoom::create(['name' => $name, 'active' => true]);
        app(ClassFeeService::class)->configure($class, ['exam_fee' => 850, 'exam_terms' => [
            ['start' => '2026-01-01', 'end' => '2026-04-30'],
            ['start' => '2026-05-01', 'end' => '2026-08-31'],
            ['start' => '2026-09-01', 'end' => '2026-12-31'],
        ], 'facility_fee' => 5000, 'facility_fee_start_date' => '2026-01-02']);

        return $class;
    }

    private function student(ClassRoom $class, array $values = []): Student
    {
        return Student::create($values + ['class_room_id' => $class->id, 'name' => 'Ameli Shanalya',
            'admission_number' => Str::random(8), 'joining_date' => '2026-01-01', 'active' => true, 'alumni' => false]);
    }

    private function page(string $type = 'exam', array $filters = [])
    {
        return $this->get(route('revenue.items.create', ['quick' => $type] + $filters));
    }

    private function payload(ClassRoom $class, Student $student, string $type = 'exam', array $extra = []): array
    {
        $schedule = $class->feeSchedules()->where('fee_type', $type)->firstOrFail();

        return $extra + ['student_id' => $student->id, 'cycle' => $schedule->id.':'.($type === 'exam' ? '2026-09-01' : '2026-01-02'),
            'submission_id' => (string) Str::uuid(), 'amount' => $schedule->amount, 'paid_at' => '2026-10-23',
            'payment_method' => 'cash', 'record_revenue' => 1, 'return_to' => 'collection',
            'search_q' => $student->admission_number, 'search_year' => 2026, 'all_years' => 1];
    }

    public function test_revenue_has_fee_collection_links_and_both_pages_are_available(): void
    {
        $this->page()->assertOk()->assertSee('Collect Exam Fee')->assertSee('Collect Facility Fee')
            ->assertSee('Find student')->assertSee('All classes')->assertSee('Exam Fee due');
        $this->page('facility')->assertOk()->assertSee('Facility Fee due');
    }

    public function test_search_finds_names_and_admission_numbers_across_classes_or_only_the_selected_class(): void
    {
        $first = $this->schoolClass();
        $second = $this->schoolClass('Grade 3');
        $one = $this->student($first, ['admission_number' => 'STU100', 'name' => 'Ameli Shanalya']);
        $two = $this->student($second, ['admission_number' => 'STU200', 'name' => 'Ameli Fathima']);
        $this->page(filters: ['q' => 'Ameli'])->assertOk()
            ->assertViewHas('students', fn ($students) => $students->total() === 2);
        $this->page(filters: ['q' => 'Ameli', 'class_room_id' => $first->id])->assertOk()
            ->assertViewHas('students', fn ($students) => $students->pluck('id')->all() === [$one->id]);
        $this->page(filters: ['q' => 'STU200'])->assertOk()
            ->assertViewHas('selectedStudent', fn ($student) => $student->id === $two->id);
        $this->page(filters: ['q' => 'STU200', 'class_room_id' => $first->id, 'student_id' => $two->id])->assertOk()
            ->assertViewHas('selectedStudent', null)->assertViewHas('students', fn ($students) => $students->total() === 0);
    }

    public function test_exam_and_facility_panels_show_only_the_selected_fee_and_use_admission_rules(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class, ['joining_date' => '2026-10-01']);
        $this->page(filters: ['student_id' => $student->id])->assertOk()
            ->assertViewHas('installments', fn ($entries) => $entries->count() === 1 && $entries->first()['cycle']['term'] === 3
                && $entries->sum(fn ($entry) => $entry['status']['due']) === 850.0)
            ->assertViewHas('selectedStatus', fn ($status) => $status['collectable'] === 850.0);
        $this->page('facility', ['student_id' => $student->id])->assertOk()
            ->assertViewHas('installments', fn ($entries) => $entries->count() === 1 && $entries->first()['cycle']['schedule']->fee_type === 'facility'
                && $entries->sum(fn ($entry) => $entry['status']['due']) === 5000.0);
    }

    public function test_collection_saves_against_the_installment_and_returns_to_the_selected_student(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class, ['joining_date' => '2026-10-01']);
        $payload = $this->payload($class, $student, extra: ['amount' => 400, 'search_class_room_id' => $class->id]);
        $url = route('class-fees.store', ['classroom' => $class, 'type' => 'exam']);
        $response = $this->post($url, $payload)->assertSessionHasNoErrors();
        $response->assertRedirect(route('revenue.items.create', ['quick' => 'exam', 'class_room_id' => $class->id,
            'q' => $student->admission_number, 'year' => 2026, 'student_id' => $student->id, 'cycle' => $payload['cycle'],
            'include_inactive' => 0, 'all_years' => 1]));
        $this->get($response->headers->get('Location'))->assertOk()
            ->assertViewHas('selectedStatus', fn ($status) => $status['paid'] === 400.0 && $status['collectable'] === 450.0);
        $this->post($url, $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('revenues', 1);
        $this->post($url, $this->payload($class, $student, extra: ['amount' => 451]))->assertSessionHasErrors('amount');
        $this->assertSame('2026-09-01', Revenue::firstOrFail()->class_fee_due_date->toDateString());
    }

    public function test_facility_collection_always_records_revenue_even_if_the_hidden_value_is_changed(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class, ['joining_date' => '2026-10-01']);
        $response = $this->post(route('class-fees.store', ['classroom' => $class, 'type' => 'facility']),
            $this->payload($class, $student, 'facility', ['record_revenue' => 0]))->assertSessionHasNoErrors();
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('All listed installments are settled.')
            ->assertViewHas('selectedStatus', fn ($status) => $status['paid'] === 5000.0 && (float) $status['due'] === 0.0);
        $this->assertDatabaseCount('revenues', 1);
        $this->assertDatabaseCount('class_fee_payment_histories', 0);
    }

    public function test_pending_cheques_reserve_the_balance_in_the_collection_panel(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class, ['joining_date' => '2026-10-01']);
        $this->post(route('class-fees.store', ['classroom' => $class, 'type' => 'exam']), $this->payload($class, $student, extra: [
            'payment_method' => 'cheque', 'cheque_bank' => 'Test Bank', 'cheque_number' => '321', 'cheque_date' => '2026-10-24',
        ]))->assertSessionHasNoErrors();
        $this->page(filters: ['student_id' => $student->id])->assertOk()->assertSee('reserved by pending cheques')
            ->assertViewHas('installments', fn ($entries) => $entries->first()['status']['pending'] === 850.0
                && (float) $entries->first()['status']['collectable'] === 0.0);
    }

    public function test_earlier_year_dues_are_included_by_default_and_can_be_filtered_to_one_year(): void
    {
        $class = $this->schoolClass();
        $schedule = $class->feeSchedules()->where('fee_type', 'exam')->firstOrFail();
        $schedule->update(['first_due_date' => '2025-01-01', 'terms' => array_map(fn ($term) => [
            'start' => str_replace('2026', '2025', $term['start']), 'end' => str_replace('2026', '2025', $term['end']),
        ], $schedule->terms)]);
        $student = $this->student($class, ['joining_date' => '2025-01-01']);
        $this->page(filters: ['student_id' => $student->id])->assertOk()
            ->assertViewHas('installments', fn ($entries) => $entries->count() === 6 && $entries->sum(fn ($entry) => $entry['status']['due']) === 5100.0)
            ->assertViewHas('selectedCycle', fn ($cycle) => $cycle['due']->toDateString() === '2025-01-01');
        $this->page(filters: ['student_id' => $student->id, 'all_years' => 0, 'year' => 2026])->assertOk()
            ->assertViewHas('installments', fn ($entries) => $entries->count() === 3 && $entries->sum(fn ($entry) => $entry['status']['due']) === 2550.0);
    }

    public function test_unavailable_cycles_and_students_without_a_fee_do_not_show_a_payment_form(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $this->page(filters: ['student_id' => $student->id, 'cycle' => '999:2026-01-01'])->assertOk()
            ->assertViewHas('selectedCycle', null)->assertDontSee('name="return_to"', false);
        $other = ClassRoom::create(['name' => 'Without fees', 'active' => true]);
        $student = $this->student($other);
        $this->page('facility', ['student_id' => $student->id])->assertOk()->assertSee('No applicable facility fee installments')
            ->assertDontSee('name="return_to"', false);
    }

    public function test_former_students_are_hidden_by_default_but_history_is_available_when_requested(): void
    {
        $old = $this->schoolClass('Previous Class');
        $new = $this->schoolClass('Current Class');
        $student = $this->student($new);
        $move = StudentPromotionHistory::create(['student_id' => $student->id, 'from_class_room_id' => $old->id, 'to_class_room_id' => $new->id, 'action' => 'transfer']);
        $move->forceFill(['created_at' => Carbon::parse('2026-08-15')])->save();
        $this->get(route('class-fees.index', ['class_room_id' => $old->id]))->assertOk()->assertDontSee($student->name)
            ->assertViewHas('tracker', fn ($tracker) => $tracker['rows']->isEmpty() && $tracker['due'] === 0.0);
        $this->get(route('class-fees.index', ['class_room_id' => $old->id, 'include_former' => 1]))->assertOk()->assertSee($student->name)
            ->assertViewHas('tracker', fn ($tracker) => $tracker['due'] === 6700.0);
        $this->get(route('class-fees.index'))->assertOk()
            ->assertViewHas('classSummaries', fn ($summaries) => $summaries->firstWhere('class.id', $old->id)['rows']->isEmpty());
        $this->page(filters: ['student_id' => $student->id])->assertOk()
            ->assertViewHas('installments', fn ($entries) => $entries->count() === 3 && $entries->sum(fn ($entry) => $entry['status']['due']) === 2550.0);
    }

    public function test_fee_collection_requires_revenue_add_permission_and_hides_inactive_students_by_default(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class, ['active' => false, 'alumni' => true]);
        $this->page(filters: ['student_id' => $student->id])->assertOk()->assertViewHas('selectedStudent', null);
        $this->page(filters: ['student_id' => $student->id, 'include_inactive' => 1])->assertOk()
            ->assertViewHas('selectedStudent', fn ($member) => $member->id === $student->id);
        $user = User::factory()->create();
        Permission::findOrCreate('revenue.add', 'web');
        $this->actingAs($user);
        $this->page()->assertForbidden();
        $this->page('facility')->assertForbidden();
        $user->givePermissionTo('revenue.add');
        $this->page()->assertOk();
    }

    public function test_collection_layout_renders_student_and_payment_details(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class, ['admission_number' => 'STU0959']);
        $this->student($class, ['name' => 'Amidu Chenitha', 'admission_number' => 'STU1066', 'joining_date' => '2026-10-01']);
        $response = $this->page(filters: ['student_id' => $student->id])->assertOk()
            ->assertSee('STU0959')->assertSee('Record exam fee payment')->assertSee('This payment is recorded in revenue');
        if ($directory = getenv('CLASS_FEE_COLLECTION_PREVIEW_DIR')) {
            if (! is_dir($directory)) {
                mkdir($directory, 0775, true);
            }
            file_put_contents($directory.'/exam-collection.html', $response->getContent());
        }
    }

    public function test_live_suggestions_match_names_numbers_and_phone_and_respect_class_and_activity_filters(): void
    {
        $first = $this->schoolClass();
        $other = $this->schoolClass('Grade 3');
        $student = $this->student($first, ['admission_number' => 'ADM123', 'phone' => '0774567890']);
        $this->student($other, ['name' => 'Other Student', 'admission_number' => 'ADM999']);
        $inactive = $this->student($first, ['name' => 'Inactive Student', 'active' => false]);
        foreach (['Shanalya', 'ADM123', '077456'] as $query) {
            $this->page(filters: ['q' => $query, 'suggest' => 1])->assertOk()->assertJsonCount(1, 'results')->assertJsonPath('results.0.id', $student->id);
        }
        $this->page(filters: ['q' => 'ADM999', 'suggest' => 1, 'class_room_id' => $first->id])->assertJsonCount(0, 'results');
        $this->page(filters: ['q' => 'Inactive', 'suggest' => 1])->assertJsonCount(0, 'results');
        $this->page(filters: ['q' => 'Inactive', 'suggest' => 1, 'include_inactive' => 1])->assertJsonPath('results.0.id', $inactive->id);
        $this->page(filters: ['q' => str_repeat('x', 101), 'suggest' => 1])->assertSessionHasErrors('q');
    }

    public function test_monthly_category_picker_includes_exam_and_facility_and_routes_to_the_matching_collection(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        RevenueCategory::create(['name' => 'Monthly Fee', 'active' => true, 'payment_type' => 'monthly', 'applies_to_all' => true]);
        $this->page('monthly', ['student_id' => $student->id])->assertOk()
            ->assertViewHas('categories', fn ($categories) => $categories->where('class_fee_type', 'exam')->count() === 1 && $categories->where('class_fee_type', 'facility')->count() === 1)
            ->assertSee('categoryChanged($event.target.value)', false);
        foreach (['exam', 'facility'] as $type) {
            $category = RevenueCategory::where('class_fee_type', $type)->firstOrFail();
            $this->get(route('revenue.items.create', ['category_id' => $category->id, 'student_id' => $student->id]))
                ->assertRedirect(route('revenue.items.create', ['quick' => $type, 'student_id' => $student->id]));
        }
    }

    public function test_save_and_view_bill_returns_the_printable_receipt_for_cash_and_bank_payments(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        foreach (['cash', 'bank_transfer'] as $method) {
            $response = $this->post(route('class-fees.store', ['classroom' => $class, 'type' => 'exam']), $this->payload($class, $student, extra: [
                'view_receipt' => 1, 'amount' => 400, 'payment_method' => $method, 'bank_name' => 'Test bank', 'bank_ref_no' => 'REF123',
            ]))->assertSessionHasNoErrors();
            $payment = Revenue::latest('id')->firstOrFail();
            $response->assertRedirect(route('revenue.items.receipt', $payment));
            $this->get($response->headers->get('Location'))->assertOk()->assertSee($payment->bill_no)->assertSee('Print Receipt')->assertSee('Term paid:')->assertSee('Back to fee collection')
                ->assertSee('Term 3')->assertDontSee('Monthly fee :');
        }
        $this->assertSame('REF123', $payment->payment_meta['ref_no']);
        $this->assertSame(400.0, app(CashBalanceService::class)->balanceOn('2026-10-23'));
        $this->get(route('reports.daily_ledger', ['from' => '2026-10-23', 'to' => '2026-10-23']))->assertOk()->assertViewHas('totalIn', 800.0);
    }

    public function test_manual_bill_numbers_are_unique_and_retries_do_not_duplicate_the_receipt(): void
    {
        Setting::updateOrCreate(['key' => 'billing.revenue.autogenerate'], ['value' => '0', 'group' => 'general']);
        $this->app->forgetInstance('settings');
        $class = $this->schoolClass();
        $student = $this->student($class);
        $payload = $this->payload($class, $student, extra: ['amount' => 300, 'bill_no' => 'CUSTOM-101']);
        $url = route('class-fees.store', ['classroom' => $class, 'type' => 'exam']);
        $this->post($url, $payload)->assertSessionHasNoErrors();
        $this->post($url, $payload)->assertSessionHasNoErrors();
        $this->post($url, $this->payload($class, $student, extra: ['amount' => 300, 'bill_no' => 'CUSTOM-101']))->assertSessionHasErrors('bill_no');
        $this->assertDatabaseCount('revenues', 1);
        $this->assertSame('CUSTOM-101', Revenue::firstOrFail()->bill_no);
    }

    public function test_facility_receipt_uses_annual_fee_labels_instead_of_monthly_fee_labels(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $this->post(route('class-fees.store', ['classroom' => $class, 'type' => 'facility']),
            $this->payload($class, $student, 'facility', ['view_receipt' => 1]))->assertSessionHasNoErrors();
        $this->get(route('revenue.items.receipt', Revenue::firstOrFail()))->assertOk()->assertSee('Facility fee :')
            ->assertSee('Facility year 2026')->assertDontSee('Monthly fee :');
    }

    public function test_cheques_require_details_stay_out_of_income_and_can_only_be_passed_on_a_valid_clearance_date(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class, ['joining_date' => '2026-10-01']);
        $url = route('class-fees.store', ['classroom' => $class, 'type' => 'exam']);
        $this->post($url, $this->payload($class, $student, extra: ['payment_method' => 'cheque']))
            ->assertSessionHasErrors(['cheque_date', 'cheque_bank', 'cheque_number']);
        $payload = $this->payload($class, $student, extra: ['payment_method' => 'cheque', 'cheque_date' => '2026-10-24',
            'cheque_bank' => 'Cheque Bank', 'cheque_number' => 'CH456', 'cheque_student_name' => 'A Shanalya', 'view_receipt' => 1]);
        $this->post($url, $payload)->assertSessionHasNoErrors();
        $payment = Revenue::firstOrFail();
        $this->assertSame('hold', $payment->payment_status);
        $this->assertSame('A Shanalya', $payment->payment_meta['student_name']);
        $this->assertSame(0.0, app(CashBalanceService::class)->balanceOn('2026-10-23'));
        $this->get(route('revenue.items.receipt', $payment))->assertOk()->assertSee('Payment Pending Confirmation')->assertSee('CHEQUE PENDING');
        $pass = route('revenue.items.cheque.passed', $payment);
        $this->post($pass, ['passed_date' => '2026-10-23'])->assertSessionHasErrors('passed_date');
        $this->post($pass, ['passed_date' => '2026-10-25'])->assertSessionHasErrors('passed_date');
        $this->travelTo(Carbon::parse('2026-10-24 12:00:00'));
        $this->post($pass, ['passed_date' => '2026-10-24'])->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $payment->fresh()->payment_status);
        $this->page(filters: ['student_id' => $student->id])->assertViewHas('installments', fn ($entries) => $entries->first()['status']['paid'] === 850.0);
        $this->assertSame(0.0, app(CashBalanceService::class)->balanceOn('2026-10-24'));
        $this->get(route('reports.daily_ledger', ['from' => '2026-10-24', 'to' => '2026-10-24']))->assertOk()->assertViewHas('totalIn', 850.0);
    }

    public function test_returned_cheques_reopen_collection_and_pending_cheques_cannot_be_entered_as_settled_history(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class, ['joining_date' => '2026-10-01']);
        $payload = $this->payload($class, $student, extra: ['payment_method' => 'cheque', 'cheque_date' => '2026-10-23',
            'cheque_bank' => 'Cheque Bank', 'cheque_number' => 'CH100']);
        $url = route('class-fees.store', ['classroom' => $class, 'type' => 'exam']);
        $this->post($url, $payload)->assertSessionHasNoErrors();
        $payment = Revenue::firstOrFail();
        $correction = ['amount' => 850, 'paid_at' => '2026-10-23', 'record_revenue' => 0];
        $this->put(route('class-fees.payments.update', ['source' => 'revenue', 'payment' => $payment->id]), $correction)->assertSessionHasErrors('record_revenue');
        $this->post(route('revenue.items.cheque.returned', $payment))->assertSessionHasNoErrors();
        $this->page(filters: ['student_id' => $student->id])->assertViewHas('selectedStatus', fn ($status) => $status['collectable'] === 850.0 && $status['pending'] === 0.0);
        $payload['submission_id'] = (string) Str::uuid();
        $payload['record_revenue'] = 0;
        $payload['return_to'] = null;
        $this->post($url, $payload)->assertSessionHasErrors('payment_method');
        $this->assertDatabaseCount('class_fee_payment_histories', 0);
    }

    public function test_cashier_can_print_own_fee_bill_but_cannot_access_another_cashiers_receipt(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $url = route('class-fees.store', ['classroom' => $class, 'type' => 'exam']);
        $this->post($url, $this->payload($class, $student, extra: ['amount' => 100]))->assertSessionHasNoErrors();
        $other = Revenue::firstOrFail();
        $cashier = User::factory()->create();
        $cashier->givePermissionTo(Permission::findOrCreate('revenue.add', 'web'));
        $this->actingAs($cashier);
        $response = $this->post($url, $this->payload($class, $student, extra: ['amount' => 100, 'view_receipt' => 1]))->assertSessionHasNoErrors();
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('Print Receipt');
        $this->get(route('revenue.items.receipt', $other))->assertForbidden();
    }

    public function test_cleared_cheques_can_be_corrected_to_historical_settlement_without_reopening_the_fee(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class, ['joining_date' => '2026-10-01']);
        $this->post(route('class-fees.store', ['classroom' => $class, 'type' => 'exam']), $this->payload($class, $student, extra: [
            'payment_method' => 'cheque', 'cheque_date' => '2026-10-23', 'cheque_bank' => 'Test Bank', 'cheque_number' => 'CH777',
        ]))->assertSessionHasNoErrors();
        $payment = Revenue::firstOrFail();
        $this->post(route('revenue.items.cheque.passed', $payment))->assertSessionHasNoErrors();
        $this->put(route('class-fees.payments.update', ['source' => 'revenue', 'payment' => $payment->id]), [
            'amount' => 850, 'paid_at' => '2026-10-23', 'record_revenue' => 0,
        ])->assertSessionHasNoErrors();
        $this->assertTrue($payment->fresh()->isCancelled());
        $this->assertDatabaseHas('class_fee_payment_histories', ['student_id' => $student->id, 'payment_method' => 'cheque', 'amount' => 850]);
        $this->page(filters: ['student_id' => $student->id])->assertViewHas('installments', fn ($entries) => $entries->first()['status']['paid'] === 850.0
            && (float) $entries->first()['status']['collectable'] === 0.0);
    }

    public function test_facility_category_is_locked_and_amount_defaults_to_the_fee_then_remaining_balance(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        foreach (['facility', 'exam'] as $type) {
            $response = $this->page($type, ['student_id' => $student->id])->assertOk();
            $dom = new \DOMDocument;
            @$dom->loadHTML($response->getContent());
            $xpath = new \DOMXPath($dom);
            $this->assertSame(1, $xpath->query('//input[@id="fee-category"][@readonly]')->length);
            $this->assertSame(0, $xpath->query('//select[@id="fee-category"]')->length);
        }
        $response = $this->page('facility', ['student_id' => $student->id])->assertSee('Pay Facility Fee')
            ->assertSee('Use remaining facility fee')->assertSee('Configured facility fee: Rs 5,000.00');
        $this->assertStringContainsString('value="5000"', $response->getContent());
        $url = route('class-fees.store', ['classroom' => $class, 'type' => 'facility']);
        $payload = $this->payload($class, $student, 'facility', ['amount' => 1200, 'revenue_category_id' => 999999]);
        $this->post($url, $payload)->assertSessionHasNoErrors();
        $payment = Revenue::firstOrFail();
        $this->assertSame($class->feeSchedules()->where('fee_type', 'facility')->first()->revenue_category_id, $payment->revenue_category_id);
        $this->page('facility', ['student_id' => $student->id])->assertOk()->assertSee('Pay Facility Fee')
            ->assertViewHas('selectedStatus', fn ($status) => $status['collectable'] === 3800.0)
            ->assertSee('value="3800"', false);
        $this->post($url, $this->payload($class, $student, 'facility', ['amount' => 3801]))->assertSessionHasErrors('amount');
    }

    public function test_settled_facility_fee_has_clear_paid_state_and_correction_links_instead_of_accepting_duplicates(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $url = route('class-fees.store', ['classroom' => $class, 'type' => 'facility']);
        $this->post($url, $this->payload($class, $student, 'facility'))->assertSessionHasNoErrors();
        $payment = Revenue::firstOrFail();
        $response = $this->page('facility', ['student_id' => $student->id])->assertOk()
            ->assertSee('Facility Fee fully paid')->assertSee('Edit / take payment')
            ->assertSee(route('class-fees.payments.edit', ['source' => 'revenue', 'payment' => $payment->id, 'collection' => 1]), false)
            ->assertDontSee('name="amount"', false);
        $this->post($url, $this->payload($class, $student, 'facility'))->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('revenues', 1);
        $cashier = User::factory()->create();
        $cashier->givePermissionTo('revenue.add');
        $this->actingAs($cashier);
        $this->page('facility', ['student_id' => $student->id])->assertOk()
            ->assertDontSee(route('class-fees.payments.edit', ['source' => 'revenue', 'payment' => $payment->id, 'collection' => 1]), false);
    }

    public function test_collection_edit_defaults_to_today_forces_revenue_and_opens_the_existing_bill(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $this->post(route('class-fees.store', ['classroom' => $class, 'type' => 'facility']),
            $this->payload($class, $student, 'facility', ['paid_at' => '2026-01-10']))->assertSessionHasNoErrors();
        $payment = Revenue::firstOrFail();
        $edit = route('class-fees.payments.edit', ['source' => 'revenue', 'payment' => $payment->id]);
        $this->get($edit)->assertOk()->assertSee('value="2026-01-10"', false)->assertSee('type="checkbox" x-model="revenue"', false);
        $this->get($edit.'?collection=1')->assertOk()->assertSee('value="2026-10-23"', false)
            ->assertSee('Save / take payment')->assertDontSee('type="checkbox" x-model="revenue"', false);
        $response = $this->put(route('class-fees.payments.update', ['source' => 'revenue', 'payment' => $payment->id]), [
            'return_to' => 'collection', 'amount' => 4000, 'paid_at' => '2026-10-20', 'record_revenue' => 0,
        ])->assertSessionHasNoErrors()->assertRedirect(route('revenue.items.receipt', $payment));
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('Facility fee :')->assertSee($payment->bill_no);
        $this->assertDatabaseCount('revenues', 1);
        $this->assertDatabaseCount('class_fee_payment_histories', 0);
        $this->assertFalse($payment->fresh()->isCancelled());
        $this->assertSame('2026-10-20', $payment->fresh()->paid_at->toDateString());
        $this->assertSame('4000.00', $payment->fresh()->amount);
        $this->page('facility', ['student_id' => $student->id])->assertOk()->assertViewHas('selectedStatus', fn ($status) => $status['collectable'] === 1000.0);
    }

    public function test_collection_edit_converts_historical_payment_to_one_revenue_bill_without_double_settling(): void
    {
        $class = $this->schoolClass();
        $student = $this->student($class);
        $this->post(route('class-fees.store', ['classroom' => $class, 'type' => 'facility']),
            $this->payload($class, $student, 'facility', ['record_revenue' => 0, 'return_to' => null, 'paid_at' => '2026-02-01']))->assertSessionHasNoErrors();
        $history = \App\Models\ClassFeePaymentHistory::firstOrFail();
        $this->get(route('class-fees.payments.edit', ['source' => 'history', 'payment' => $history->id, 'collection' => 1]))
            ->assertOk()->assertSee('value="2026-10-23"', false)->assertSee('Method for the revenue entry')
            ->assertDontSee('type="checkbox" x-model="revenue"', false);
        $this->put(route('class-fees.payments.update', ['source' => 'history', 'payment' => $history->id]), [
            'return_to' => 'collection', 'amount' => 5000, 'paid_at' => '2026-10-23', 'record_revenue' => 0, 'payment_method' => 'cash',
        ])->assertSessionHasNoErrors()->assertRedirect(route('revenue.items.receipt', Revenue::firstOrFail()));
        $this->assertTrue($history->fresh()->isCancelled());
        $this->assertDatabaseCount('revenues', 1);
        $this->page('facility', ['student_id' => $student->id])->assertOk()->assertSee('Facility Fee fully paid')
            ->assertViewHas('installments', fn ($entries) => $entries->first()['status']['paid'] === 5000.0);
        $this->assertSame(5000.0, app(CashBalanceService::class)->balanceOn('2026-10-23'));
    }
}
