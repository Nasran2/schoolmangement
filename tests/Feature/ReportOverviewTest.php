<?php

namespace Tests\Feature;

use App\Models\ClassFeeSchedule;
use App\Models\ClassRoom;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\OwnerTransfer;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\Seminar;
use App\Models\SeminarTeacherPayment;
use App\Models\Student;
use App\Models\StudentPromotionHistory;
use App\Models\Teacher;
use App\Models\TeacherSalaryPayment;
use App\Models\User;
use App\Services\Billing\ClassFeeService;
use App\Services\ReportOverview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->forgetInstance('settings');
        $this->travelTo(now()->setDate(2026, 10, 21)->setTime(12, 0));
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin', 'web'));
        $this->actingAs($admin);
    }

    private function receipt(array $data = []): Revenue
    {
        $category = RevenueCategory::firstOrCreate(['name' => 'Overview income'], ['payment_type' => 'one_time', 'active' => true]);

        return Revenue::create(array_replace(['revenue_category_id' => $category->id, 'amount' => 1000, 'paid_at' => '2026-10-21', 'payment_status' => 'confirmed', 'payment_method' => 'cash'], $data));
    }

    public function test_today_is_default_and_financial_cards_reconcile_to_their_complete_details(): void
    {
        $student = Student::create(['name' => 'Collection student', 'admission_number' => 'COL-1', 'active' => true, 'joining_date' => '2026-10-01']);
        $receipt = $this->receipt(['student_id' => $student->id, 'bill_no' => 'COLLECTION-1']);
        $receipt->adjustments()->create(['type' => 'refund', 'amount' => 100, 'reason' => 'Correction']);
        $this->receipt(['amount' => 200, 'bill_no' => 'OTHER-INCOME']);
        $this->receipt(['amount' => 50, 'payment_status' => 'paid']);
        foreach (['hold', 'pending', 'rejected', 'cancelled'] as $status) {
            $this->receipt(['payment_status' => $status, 'bill_no' => 'EXCLUDED-'.$status]);
        }
        $this->receipt(['cancelled_at' => now(), 'bill_no' => 'EXCLUDED-cancelled-date']);
        $this->receipt(['paid_at' => '2026-10-20', 'amount' => 300]);
        $expense = Expense::create(['expense_category_id' => ExpenseCategory::create(['name' => 'Supplies'])->id, 'expense_date' => '2026-10-21', 'amount' => 100]);
        $teacher = Teacher::create(['name' => 'Paid teacher', 'salary_amount' => 1000, 'joining_date' => '2026-01-01', 'active' => true]);
        TeacherSalaryPayment::create(['teacher_id' => $teacher->id, 'payment_month' => '2026-10', 'paid_at' => '2026-10-21', 'amount' => 300, 'base_salary' => 300]);
        $seminar = Seminar::create(['name' => 'Overview seminar', 'date' => '2026-10-21']);
        SeminarTeacherPayment::create(['seminar_id' => $seminar->id, 'expense_id' => $expense->id, 'paid_at' => '2026-10-21', 'amount' => 100]);
        SeminarTeacherPayment::create(['seminar_id' => $seminar->id, 'paid_at' => '2026-10-21', 'amount' => 50]);
        OwnerTransfer::create(['submission_id' => Str::uuid(), 'transferred_at' => '2026-10-21', 'amount' => 90, 'created_by' => auth()->id()]);

        $response = $this->get(route('reports.index'))->assertOk()
            ->assertViewHas('overviewFilters', fn ($filters) => $filters['period'] === 'today' && $filters['from'] === '2026-10-21' && $filters['to'] === '2026-10-21')
            ->assertViewHas('overviewCards', fn ($cards) => $cards->count() === 7 && $cards['collection']['amount'] === 900.0 && $cards['revenue']['amount'] === 1150.0 && $cards['expense']['amount'] === 450.0);
        foreach ($response->viewData('overviewCards') as $card) {
            $this->get($card['url'])->assertOk()->assertViewHas('total', $card['amount'])->assertViewHas('totalCount', $card['count']);
        }
        $this->get(route('reports.overview.details', 'collection'))->assertSee('COLLECTION-1')->assertDontSee('OTHER-INCOME')->assertDontSee('EXCLUDED-');
        $this->get(route('reports.overview.details', 'revenue'))->assertSee('OTHER-INCOME')->assertDontSee('EXCLUDED-');
        $this->get(route('reports.index', ['period' => 'yesterday']))->assertOk()->assertViewHas('overviewCards', fn ($cards) => $cards['revenue']['amount'] === 300.0 && $cards['expense']['amount'] === 0.0);
    }

    public function test_pending_cards_include_partial_salary_and_all_due_fee_cycles_but_not_future_or_inactive_students(): void
    {
        $category = RevenueCategory::create(['name' => 'Monthly Fee', 'payment_type' => 'monthly', 'active' => true]);
        $class = ClassRoom::create(['name' => 'Grade 2', 'monthly_fee' => 1000, 'monthly_fee_revenue_category_id' => $category->id]);
        app(ClassFeeService::class)->configure($class, ['exam_fee' => 500, 'exam_fee_start_date' => '2026-07-20', 'facility_fee' => 1200, 'facility_fee_start_date' => '2026-10-20']);
        $student = Student::create(['name' => 'Outstanding student', 'admission_number' => 'DUE-1', 'class_room_id' => $class->id,
            'joining_date' => '2026-07-01', 'fee_start_date' => '2026-10-01', 'monthly_fee' => 1000, 'active' => true, 'alumni' => false]);
        $exam = $class->feeSchedules()->where('fee_type', 'exam')->firstOrFail();
        $payment = $this->receipt(['revenue_category_id' => $exam->revenue_category_id, 'student_id' => $student->id,
            'class_fee_schedule_id' => $exam->id, 'class_fee_due_date' => '2026-07-20', 'amount' => 200]);
        $payment->adjustments()->create(['type' => 'waiver', 'amount' => 100, 'reason' => 'Approved']);
        $payment->adjustments()->create(['type' => 'refund', 'amount' => 50, 'reason' => 'Correction']);
        Student::create(['name' => 'Inactive student', 'admission_number' => 'INACTIVE', 'class_room_id' => $class->id, 'joining_date' => '2026-01-01', 'active' => false]);
        Student::create(['name' => 'Alumni student', 'admission_number' => 'ALUMNI', 'class_room_id' => $class->id, 'joining_date' => '2026-01-01', 'active' => true, 'alumni' => true]);
        $teacher = Teacher::create(['name' => 'Partial teacher', 'salary_amount' => 1000, 'active' => true, 'joining_date' => '2026-01-01']);
        TeacherSalaryPayment::create(['teacher_id' => $teacher->id, 'paid_at' => '2026-10-21', 'payment_month' => '2026-10', 'base_salary' => 400, 'amount' => 350]);
        // A late payment for a different payroll month does not settle October's salary.
        TeacherSalaryPayment::create(['teacher_id' => $teacher->id, 'paid_at' => '2026-10-20', 'payment_month' => '2026-09', 'base_salary' => 1000, 'amount' => 1000]);
        Teacher::create(['name' => 'Inactive teacher', 'salary_amount' => 9000, 'active' => false]);
        Teacher::create(['name' => 'Future teacher', 'salary_amount' => 9000, 'active' => true, 'joining_date' => '2026-11-01']);

        $this->get(route('reports.index'))->assertOk()->assertViewHas('overviewCards', fn ($cards) => $cards['teachers']['amount'] === 600.0 && $cards['teachers']['count'] === 1 && $cards['students']['amount'] === 1000.0
            && $cards['exam']['amount'] === 750.0 && $cards['exam']['count'] === 2 && $cards['facility']['amount'] === 1200.0);
        $this->get(route('reports.overview.details', 'exam'))->assertOk()->assertSee('Outstanding student')->assertDontSee('Inactive student')->assertDontSee('Alumni student')
            ->assertViewHas('items', fn ($items) => $items->total() === 2 && collect($items->items())->pluck('date')->all() === ['2026-07-20', '2026-10-20']);
        // Historical transaction filters must not turn today's balance into a false historical snapshot.
        $this->get(route('reports.index', ['period' => 'custom', 'from' => '2025-01-01', 'to' => '2025-12-31']))->assertOk()
            ->assertViewHas('overviewCards', fn ($cards) => $cards['exam']['amount'] === 750.0 && $cards['teachers']['amount'] === 600.0);
    }

    public function test_date_validation_search_and_pagination_keep_the_totals_and_filters(): void
    {
        foreach (range(1, 35) as $number) {
            $this->receipt(['bill_no' => 'ROW-'.$number, 'amount' => 10, 'paid_at' => '2026-10-10']);
        }
        $filters = ['period' => 'custom', 'from' => '2026-10-01', 'to' => '2026-10-15'];
        $this->get(route('reports.overview.details', ['metric' => 'revenue'] + $filters))->assertOk()->assertViewHas('total', 350.0)
            ->assertViewHas('items', fn ($items) => $items->total() === 35 && $items->count() === 30 && str_contains($items->nextPageUrl(), 'from=2026-10-01'));
        $this->get(route('reports.overview.details', ['metric' => 'revenue', 'q' => 'ROW-35'] + $filters))->assertOk()
            ->assertViewHas('total', 350.0)->assertViewHas('filteredTotal', 10.0)->assertViewHas('items', fn ($items) => $items->total() === 1);
        foreach ([['period' => 'unknown'], ['period' => 'custom', 'from' => 'bad', 'to' => '2026-10-21'], ['period' => 'custom', 'from' => '2026-10-22', 'to' => '2026-10-21'], ['period' => 'custom']] as $invalid) {
            $this->get(route('reports.index', $invalid))->assertSessionHasErrors();
        }
        $this->get(route('reports.overview.details', 'missing'))->assertNotFound();
    }

    public function test_each_card_and_its_details_require_the_matching_permission_and_hr_cannot_access_finances(): void
    {
        foreach (ReportOverview::METRICS as $metric => $settings) {
            $user = User::factory()->create();
            $user->givePermissionTo('reports.view');
            $this->actingAs($user)->get(route('reports.index'))->assertOk()->assertViewHas('overviewCards', fn ($cards) => $cards->isEmpty());
            $this->get(route('reports.overview.details', $metric))->assertForbidden();
            $user->givePermissionTo($settings['permissions'][0]);
            $this->get(route('reports.index'))->assertOk()->assertViewHas('overviewCards', fn ($cards) => $cards->has($metric));
            $this->get(route('reports.overview.details', $metric))->assertOk();
        }
        $hr = User::factory()->create();
        $hr->assignRole('HR admin');
        $this->actingAs($hr)->get(route('reports.index'))->assertForbidden();
        $this->get(route('reports.overview.details', 'revenue'))->assertForbidden();
    }

    public function test_grade_year_session_and_status_filters_use_stable_exam_sessions(): void
    {
        $grade = ClassRoom::create(['name' => 'Grade 2']);
        $other = ClassRoom::create(['name' => 'Grade 3']);
        $fees = app(ClassFeeService::class);
        $fees->configure($grade, ['exam_fee' => 500, 'exam_fee_start_date' => '2026-04-20', 'facility_fee' => 1200, 'facility_fee_start_date' => '2025-01-15']);
        $fees->configure($other, ['exam_fee' => 700, 'exam_fee_start_date' => '2026-01-20']);
        $student = Student::create(['name' => 'Grade Two Student', 'admission_number' => 'SESSION-2', 'class_room_id' => $grade->id, 'joining_date' => '2025-01-01', 'active' => true, 'alumni' => false]);
        Student::create(['name' => 'Grade Three Student', 'admission_number' => 'SESSION-3', 'class_room_id' => $other->id, 'joining_date' => '2025-01-01', 'active' => true, 'alumni' => false]);
        $schedule = $grade->feeSchedules()->where('fee_type', 'exam')->firstOrFail();
        foreach ([['2026-04-20', 500, 'confirmed'], ['2026-07-20', 100, 'confirmed'], ['2026-10-20', 200, 'hold']] as [$due, $amount, $status]) {
            $this->receipt(['student_id' => $student->id, 'revenue_category_id' => $schedule->revenue_category_id, 'class_fee_schedule_id' => $schedule->id,
                'class_fee_due_date' => $due, 'amount' => $amount, 'payment_status' => $status]);
        }
        $base = ['metric' => 'exam', 'class_room_id' => $grade->id, 'installment_year' => 2026];
        $this->get(route('reports.overview.details', $base))->assertOk()->assertViewHas('filters', fn ($filters) => $filters['exam_sessions'] === [1, 2, 3, 4])
            ->assertViewHas('filteredTotal', 900.0)->assertViewHas('items', fn ($items) => collect($items->items())->pluck('exam_session')->all() === [2, 3])
            ->assertSee('Session 2')->assertSee('Session 3')->assertDontSee('Grade Three Student');
        $this->get(route('reports.overview.details', $base + ['exam_sessions' => [2], 'balance_status' => 'partial']))->assertOk()
            ->assertViewHas('filteredTotal', 400.0)->assertViewHas('items', fn ($items) => $items->total() === 1);
        $this->get(route('reports.overview.details', $base + ['exam_sessions' => [3], 'balance_status' => 'pending']))->assertOk()
            ->assertViewHas('filteredTotal', 500.0)->assertSee('Pending cheque');
        $this->get(route('reports.overview.details', $base + ['exam_sessions' => [1]]))->assertOk()->assertViewHas('filteredTotal', 0.0);
        $this->get(route('reports.overview.details', $base + ['sessions_selected' => 1]))->assertOk()
            ->assertViewHas('filters', fn ($filters) => $filters['exam_sessions'] === [])->assertViewHas('filteredTotal', 0.0);
        $this->get(route('reports.overview.details', ['metric' => 'exam', 'class_room_id' => $other->id, 'exam_sessions' => [4]]))->assertOk()
            ->assertViewHas('filteredTotal', 700.0)->assertViewHas('items', fn ($items) => collect($items->items())->pluck('date')->all() === ['2026-10-20']);
        foreach ([2025, 2026] as $year) {
            $this->get(route('reports.overview.details', ['metric' => 'facility', 'class_room_id' => $grade->id, 'installment_year' => $year]))
                ->assertOk()->assertViewHas('filteredTotal', 1200.0)->assertViewHas('items', fn ($items) => $items->total() === 1 && $items->first()['installment_year'] === $year);
        }
    }

    public function test_exam_session_number_survives_fee_versions_and_grade_changes(): void
    {
        $oldGrade = ClassRoom::create(['name' => 'Earlier Grade']);
        $newGrade = ClassRoom::create(['name' => 'Current Grade']);
        $category = RevenueCategory::create(['name' => 'Exam Fee', 'class_fee_type' => 'exam', 'payment_type' => '3_months']);
        $oldRule = ClassFeeSchedule::create(['class_room_id' => $oldGrade->id, 'revenue_category_id' => $category->id, 'fee_type' => 'exam', 'amount' => 500, 'first_due_date' => '2026-01-20', 'ends_on' => '2026-07-19']);
        ClassFeeSchedule::create(['class_room_id' => $oldGrade->id, 'revenue_category_id' => $category->id, 'fee_type' => 'exam', 'amount' => 900, 'first_due_date' => '2026-07-20']);
        $student = Student::create(['name' => 'Promoted Student', 'admission_number' => 'PROMOTED', 'class_room_id' => $newGrade->id, 'joining_date' => '2026-01-01', 'active' => true, 'alumni' => false]);
        $move = StudentPromotionHistory::create(['student_id' => $student->id, 'from_class_room_id' => $oldGrade->id, 'to_class_room_id' => $newGrade->id, 'action' => 'promote', 'academic_year' => '2026-2027']);
        $move->created_at = '2026-08-01';
        $move->save();
        $this->receipt(['student_id' => $student->id, 'revenue_category_id' => $category->id, 'class_fee_schedule_id' => $oldRule->id, 'class_fee_due_date' => '2026-01-20', 'amount' => 500]);
        $this->get(route('reports.overview.details', ['metric' => 'exam', 'class_room_id' => $oldGrade->id, 'exam_sessions' => [3]]))->assertOk()
            ->assertViewHas('filteredTotal', 900.0)->assertSee('Promoted Student')->assertViewHas('items', fn ($items) => $items->first()['date'] === '2026-07-20');
        $this->get(route('reports.overview.details', ['metric' => 'exam', 'class_room_id' => $newGrade->id]))->assertOk()->assertViewHas('filteredTotal', 0.0);
    }

    public function test_payroll_month_teacher_and_partial_filters_recalculate_remaining_salary(): void
    {
        $teacher = Teacher::create(['name' => 'Partial Teacher', 'salary_amount' => 1000, 'active' => true, 'joining_date' => '2026-01-01']);
        Teacher::create(['name' => 'Unpaid Teacher', 'salary_amount' => 500, 'active' => true, 'joining_date' => '2026-01-01']);
        Teacher::create(['name' => 'Later Teacher', 'salary_amount' => 9000, 'active' => true, 'joining_date' => '2026-10-01']);
        TeacherSalaryPayment::create(['teacher_id' => $teacher->id, 'paid_at' => '2026-10-10', 'payment_month' => '2026-09', 'base_salary' => 300, 'amount' => 250]);
        $this->get(route('reports.overview.details', ['metric' => 'teachers', 'payroll_month' => '2026-09', 'teacher_id' => $teacher->id, 'balance_status' => 'partial']))->assertOk()
            ->assertViewHas('filteredTotal', 700.0)->assertViewHas('items', fn ($items) => $items->total() === 1)->assertSee('September 2026');
        $this->get(route('reports.overview.details', ['metric' => 'teachers', 'payroll_month' => '2026-09', 'balance_status' => 'unpaid']))->assertOk()
            ->assertViewHas('filteredTotal', 500.0)->assertDontSee('Later Teacher</td>', false);
        $this->get(route('reports.overview.details', ['metric' => 'teachers', 'teacher_id' => $teacher->id]))->assertOk()->assertViewHas('filteredTotal', 1000.0);
    }

    public function test_student_grade_minimum_balance_and_sort_filters_and_fee_pagination_are_preserved(): void
    {
        $category = RevenueCategory::create(['name' => 'Monthly Fee', 'payment_type' => 'monthly', 'active' => true]);
        $grade = ClassRoom::create(['name' => 'Grade filter', 'monthly_fee' => 1000, 'monthly_fee_revenue_category_id' => $category->id]);
        $other = ClassRoom::create(['name' => 'Other grade', 'monthly_fee' => 9000, 'monthly_fee_revenue_category_id' => $category->id]);
        app(ClassFeeService::class)->configure($grade, ['exam_fee' => 500, 'exam_fee_start_date' => '2026-10-01']);
        foreach (range(1, 35) as $number) {
            $student = Student::create(['name' => 'Student '.$number, 'admission_number' => 'FILTER-'.$number, 'class_room_id' => $grade->id, 'monthly_fee' => 1000 + $number, 'joining_date' => '2026-10-01', 'fee_start_date' => '2026-10-01', 'active' => true, 'alumni' => false]);
            $student->monthlyFeeOverrides()->create(['year' => 2026, 'month' => 10, 'fee_amount' => 1000 + $number]);
        }
        Student::create(['name' => 'Other student', 'admission_number' => 'OTHER', 'class_room_id' => $other->id, 'monthly_fee' => 9000, 'joining_date' => '2026-10-01', 'fee_start_date' => '2026-10-01', 'active' => true, 'alumni' => false]);
        $this->get(route('reports.overview.details', ['metric' => 'students', 'class_room_id' => $grade->id, 'min_amount' => 1030, 'sort' => 'amount_desc']))->assertOk()
            ->assertViewHas('items', fn ($items) => $items->total() === 6 && $items->first()['amount'] === 1035.0)
            ->assertViewHas('filteredTotal', 6195.0)->assertDontSee('Other student');
        $this->get(route('reports.overview.details', ['metric' => 'exam', 'class_room_id' => $grade->id, 'installment_year' => 2026, 'exam_sessions' => [1], 'sessions_selected' => 1]))->assertOk()
            ->assertViewHas('filteredTotal', 17500.0)->assertViewHas('items', fn ($items) => $items->total() === 35 && $items->count() === 30 && str_contains($items->nextPageUrl(), 'exam_sessions%5B0%5D=1') && str_contains($items->nextPageUrl(), 'class_room_id='.$grade->id));
    }

    public function test_invalid_detail_filters_are_rejected(): void
    {
        foreach ([['class_room_id' => 999999], ['installment_year' => 1900], ['exam_sessions' => [5]], ['exam_sessions' => [1, 1]], ['exam_sessions' => '1'], ['min_amount' => -1], ['balance_status' => 'paid'], ['sort' => 'sql']] as $invalid) {
            $this->get(route('reports.overview.details', ['metric' => 'exam'] + $invalid))->assertSessionHasErrors();
        }
        foreach ([['payroll_month' => '2026-13'], ['payroll_month' => '2026-11'], ['teacher_id' => 999999]] as $invalid) {
            $this->get(route('reports.overview.details', ['metric' => 'teachers'] + $invalid))->assertSessionHasErrors();
        }
    }
}
