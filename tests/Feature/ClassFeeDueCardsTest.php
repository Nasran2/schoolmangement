<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\StudentPromotionHistory;
use App\Models\User;
use App\Services\Billing\ClassFeePaymentService;
use App\Services\Billing\ClassFeeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClassFeeDueCardsTest extends TestCase
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

    private function school(int $exam = 1000, int $facility = 2000, int $year = 2026): ClassRoom
    {
        $class = ClassRoom::create(['name' => 'Class '.Str::random(6), 'active' => true]);
        app(ClassFeeService::class)->configure($class, ['exam_fee' => $exam, 'exam_terms' => [
            ['start' => "$year-01-01", 'end' => "$year-04-30"],
            ['start' => "$year-05-01", 'end' => "$year-08-31"],
            ['start' => "$year-09-01", 'end' => "$year-12-31"],
        ], 'facility_fee' => $facility, 'facility_fee_start_date' => "$year-01-15"]);

        return $class;
    }

    private function student(ClassRoom $class, array $values = []): Student
    {
        return Student::create($values + ['name' => 'Student '.Str::random(6), 'admission_number' => Str::random(8),
            'class_room_id' => $class->id, 'joining_date' => '2025-01-01', 'active' => true, 'alumni' => false]);
    }

    private function pay(ClassRoom $class, Student $student, string $date, float $amount, string $type = 'exam', array $extra = []): mixed
    {
        $schedule = $class->feeSchedules()->where('fee_type', $type)->firstOrFail();

        return app(ClassFeePaymentService::class)->collect($class, $type, $extra + [
            'student_id' => $student->id, 'cycle' => $schedule->id.':'.$date, 'submission_id' => (string) Str::uuid(),
            'amount' => $amount, 'paid_at' => '2026-10-23', 'payment_method' => 'cash',
        ], auth()->user());
    }

    private function register(array $filters = []): TestResponse
    {
        return $this->get(route('class-fees.index', $filters + ['year' => 2026, 'type' => 'exam']))->assertOk();
    }

    private function assertBalances(TestResponse $response, array $expected): void
    {
        $cards = $response->viewData('dueBreakdown');
        $this->assertSame($expected, array_column($cards, 'due'));
        $this->assertEqualsWithDelta($response->viewData('tracker')['due'], array_sum(array_column($cards, 'due')), 0.001);
    }

    public function test_cards_match_partial_historical_waived_and_pending_balances_and_joining_rules(): void
    {
        $class = $this->school();
        $student = $this->student($class);
        $this->student($class, ['joining_date' => '2026-10-01']);
        $partial = $this->pay($class, $student, '2026-01-01', 400);
        $partial->adjustments()->create(['student_id' => $student->id, 'type' => 'waiver', 'amount' => 100,
            'reason' => 'Approved concession', 'created_by' => auth()->id()]);
        $this->pay($class, $student, '2026-05-01', 1000, extra: ['record_revenue' => 0]);
        $this->pay($class, $student, '2026-09-01', 250, extra: ['payment_method' => 'cheque',
            'cheque_bank' => 'Test bank', 'cheque_number' => '12345', 'cheque_date' => '2026-10-24']);
        $this->pay($class, $student, '2026-01-15', 500, 'facility');

        $response = $this->register(['class_room_id' => $class->id]);
        $this->assertBalances($response, [500.0, 0.0, 2000.0, 3500.0]);
        $response->assertSee('Due in Term 1')->assertSee('Due in Term 2')->assertSee('Due in Term 3')->assertSee('Due in Facility Fees');
        $this->assertBalances($this->register(['class_room_id' => $class->id, 'status' => 'pending']), [500.0, 0.0, 1000.0, 1500.0]);
        $this->assertBalances($this->register(['class_room_id' => $class->id, 'q' => $student->admission_number]), [500.0, 0.0, 1000.0, 1500.0]);
    }

    public function test_overview_aggregates_classes_and_respects_search_status_and_fee_selection(): void
    {
        $first = $this->school();
        $student = $this->student($first, ['name' => 'Alice Search']);
        $this->pay($first, $student, '2026-01-01', 1000);
        $second = $this->school(1500, 2500);
        $this->student($second, ['name' => 'Bob Search']);
        $this->assertBalances($this->register(), [1500.0, 2500.0, 2500.0, 4500.0]);
        $this->assertBalances($this->register(['q' => 'Alice Search']), [0.0, 1000.0, 1000.0, 2000.0]);
        $this->assertBalances($this->register(['status' => 'partial']), [0.0, 1000.0, 1000.0, 2000.0]);
        $this->assertBalances($this->register(['class_room_id' => $second->id]), [1500.0, 1500.0, 1500.0, 2500.0]);
        $response = $this->register(['exam_selection' => 1, 'exam_dates' => ['2026-09-01'], 'include_facility' => 0]);
        $this->assertBalances($response, [0.0, 0.0, 2500.0, 0.0]);
        $this->assertFalse($response->viewData('dueBreakdown')['term_1']['selected']);
        $this->assertTrue($response->viewData('dueBreakdown')['term_3']['selected']);
        $this->assertFalse($response->viewData('dueBreakdown')['facility']['selected']);
        $this->assertBalances($this->register(['type' => 'facility']), [0.0, 0.0, 0.0, 4500.0]);
        $this->assertBalances($this->register(['exam_selection' => 1, 'include_facility' => 0]), [0.0, 0.0, 0.0, 0.0]);
    }

    public function test_cards_exclude_future_terms_and_follow_selected_year_or_all_years(): void
    {
        $class = $this->school(year: 2025);
        $this->student($class);
        $this->travelTo(Carbon::parse('2026-03-01'));
        $this->assertBalances($this->register(), [1000.0, 0.0, 0.0, 2000.0]);
        $this->assertBalances($this->register(['year' => 2025]), [1000.0, 1000.0, 1000.0, 2000.0]);
        $this->assertBalances($this->register(['all_years' => 1]), [2000.0, 1000.0, 1000.0, 4000.0]);
        $this->assertBalances($this->register(['year' => 2027]), [0.0, 0.0, 0.0, 0.0]);
    }

    public function test_inactive_and_former_class_students_follow_the_register_options(): void
    {
        $class = $this->school();
        $destination = ClassRoom::create(['name' => 'Destination', 'active' => true]);
        $this->student($class);
        $this->student($class, ['active' => false]);
        $former = $this->student($destination);
        $move = StudentPromotionHistory::create(['student_id' => $former->id, 'from_class_room_id' => $class->id,
            'to_class_room_id' => $destination->id, 'action' => 'transfer']);
        $move->forceFill(['created_at' => Carbon::parse('2026-06-01')])->save();
        $this->assertBalances($this->register(['class_room_id' => $class->id]), [1000.0, 1000.0, 1000.0, 2000.0]);
        $this->assertBalances($this->register(['class_room_id' => $class->id, 'include_inactive' => 1]), [2000.0, 2000.0, 2000.0, 4000.0]);
        $this->assertBalances($this->register(['class_room_id' => $class->id, 'include_former' => 1]), [2000.0, 2000.0, 1000.0, 4000.0]);
    }

    public function test_legacy_term_numbers_remain_stable_when_only_later_dates_are_selected(): void
    {
        $class = ClassRoom::create(['name' => 'Earlier quarterly calendar', 'active' => true]);
        app(ClassFeeService::class)->configure($class, ['exam_fee' => 900, 'exam_fee_start_date' => '2026-01-20']);
        $this->student($class);
        $response = $this->register(['exam_selection' => 1, 'exam_dates' => ['2026-07-20'], 'include_facility' => 0]);
        $this->assertBalances($response, [0.0, 0.0, 900.0, 0.0]);
        $response = $this->register();
        $this->assertSame(['term_1', 'term_2', 'term_3', 'term_4', 'facility'], array_keys($response->viewData('dueBreakdown')));
        $this->assertBalances($response, [900.0, 900.0, 900.0, 900.0, 0.0]);
    }

    public function test_empty_register_keeps_four_zero_balance_cards(): void
    {
        $this->assertBalances($this->register(), [0.0, 0.0, 0.0, 0.0]);
    }
}
