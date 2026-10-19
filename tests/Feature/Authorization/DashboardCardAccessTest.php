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
