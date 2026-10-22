<?php

namespace Tests\Feature\Authorization;

use App\Models\Revenue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardCardAccessTest extends TestCase
{
    use RefreshDatabase;

    public static function cards(): array
    {
        return [
            ['total_revenue', 'Total Revenue'],
            ['total_expenses', 'Total Expenses'],
            ['net_profit', 'Net Profit / Loss'],
            ['cash_flow', 'Cash Flow ('],
            ['revenue_vs_expense', 'Revenue vs Expense ('],
            ['due_students', 'Students with Due Payments'],
            ['revenue_category_breakdown', 'Revenue Category Breakdown'],
            ['expense_category_breakdown', 'Expense Category Breakdown'],
            ['upcoming_teacher_payments', 'Upcoming Teacher Salary Payments'],
            ['enrollment_trend', 'Student Enrollment Trend ('],
            ['notifications', 'System Notifications & Reminders'],
            ['recent_activity', 'Filtered audit logs'],
            ['exam_fees', 'data-fee-widget="exam"'],
            ['facility_fees', 'data-fee-widget="facility"'],
        ];
    }

    #[DataProvider('cards')]
    public function test_each_card_is_visible_only_with_its_permission(string $widget, string $title): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('dashboard.view');
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertDontSee($title, false);
        $user->givePermissionTo('dashboard.widget.'.$widget.'.view');
        $response = $this->get(route('dashboard'))->assertOk()->assertSee($title, false);
        foreach (self::cards() as [$otherWidget, $otherTitle]) {
            if ($otherWidget !== $widget) {
                $response->assertDontSee($otherTitle, false);
            }
        }
        $user->revokePermissionTo('dashboard.widget.'.$widget.'.view');
        $this->get(route('dashboard'))->assertOk()->assertDontSee($title, false);
    }

    public function test_financial_cards_and_charts_match_and_exclude_unconfirmed_payments(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('Admin', 'web'));
        $category = \App\Models\RevenueCategory::create(['name' => 'Fees', 'payment_type' => 'one_time']);
        foreach (['confirmed' => 1500, 'hold' => 2000, 'rejected' => 3000] as $status => $amount) {
            Revenue::create(['revenue_category_id' => $category->id, 'amount' => $amount, 'payment_status' => $status, 'paid_at' => now()->toDateString()]);
        }
        $expenseCategory = \App\Models\ExpenseCategory::create(['name' => 'Supplies']);
        \App\Models\Expense::create(['expense_category_id' => $expenseCategory->id, 'amount' => 500, 'expense_date' => now()->toDateString()]);
        $teacher = \App\Models\Teacher::create(['name' => 'Paid Teacher', 'salary_amount' => 200, 'active' => true]);
        \App\Models\TeacherSalaryPayment::create(['teacher_id' => $teacher->id, 'amount' => 200, 'base_salary' => 200, 'paid_at' => now()->toDateString(), 'payment_month' => now()->format('Y-m')]);
        $this->actingAs($user)->get(route('dashboard', ['range' => 'today']))->assertOk()
            ->assertViewHas('totalRevenue', 1500.0)->assertViewHas('totalExpenses', 700.0)->assertViewHas('netProfit', 800.0)
            ->assertViewHas('dashboardData', fn ($data) => array_sum($data['monthly']['revenue']) === 1500.0
                && array_sum($data['monthly']['expense']) === 700.0
                && array_sum($data['cashFlow']['data']) === 800.0
                && array_sum($data['expCats']['data']) === 700.0);
    }

    public function test_due_cards_use_current_balances_and_full_counts_before_limiting_lists(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('Admin', 'web'));
        $category = \App\Models\RevenueCategory::create(['name' => 'Monthly Fee', 'payment_type' => 'monthly', 'active' => true]);
        $class = \App\Models\ClassRoom::create(['name' => 'Grade 1', 'monthly_fee' => 5000, 'monthly_fee_revenue_category_id' => $category->id]);
        for ($i = 1; $i <= 7; $i++) {
            \App\Models\Student::create(['admission_number' => 'DUE'.$i, 'name' => 'Due Student '.$i,
                'class_room_id' => $class->id, 'monthly_fee' => 5000, 'due_amount' => 0,
                'fee_start_date' => now()->startOfMonth()->toDateString(), 'active' => true]);
            \App\Models\Teacher::create(['name' => 'Due Teacher '.$i, 'salary_amount' => 1000, 'active' => true]);
        }
        \App\Models\Teacher::create(['name' => 'No Salary Teacher', 'salary_amount' => 0, 'active' => true]);
        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertViewHas('dueStudentCount', 7)->assertViewHas('dueTeacherCount', 7)
            ->assertViewHas('dueStudents', fn ($students) => $students->count() === 5)
            ->assertViewHas('dueTeachers', fn ($teachers) => $teachers->count() === 5)
            ->assertViewHas('dueTeachersTotal', 7000.0);
    }

    public function test_dashboard_only_access_does_not_expose_chart_data(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('dashboard.view');
        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $response->assertViewHas('dashboardData', function ($data) {
            foreach ($data as $series) {
                foreach ($series as $values) {
                    if ($values !== []) {
                        return false;
                    }
                }
            }

            return true;
        });
    }

    public function test_admin_has_all_cards_even_without_explicit_permission_assignments(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('Admin', 'web'));
        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();
        foreach (self::cards() as [, $title]) {
            $response->assertSee($title, false);
        }
    }

    public function test_dashboard_validates_custom_dates_and_get_requests_do_not_confirm_cheques(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('dashboard.view');
        $revenue = Revenue::create([
            'revenue_category_id' => \App\Models\RevenueCategory::create(['name' => 'Fees', 'payment_type' => 'one_time'])->id,
            'amount' => 1500, 'payment_method' => 'cheque', 'payment_status' => 'hold',
            'cheque_date' => now()->subDays(30)->toDateString(), 'paid_at' => now()->toDateString(),
        ]);
        $this->actingAs($user)->get(route('dashboard', ['range' => 'custom', 'from' => 'invalid-date', 'to' => '2026-10-19']))->assertSessionHasErrors('from');
        $this->get(route('dashboard'))->assertOk();
        $this->assertSame('hold', $revenue->fresh()->payment_status);
        $this->get(route('dashboard', ['range' => 'custom', 'from' => '2026-10-01', 'to' => '2026-10-19']))->assertOk();
    }
}
