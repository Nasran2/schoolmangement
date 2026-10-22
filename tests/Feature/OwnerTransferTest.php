<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\OpeningBalance;
use App\Models\Revenue;
use App\Models\RevenueAdjustment;
use App\Models\RevenueCategory;
use App\Models\Teacher;
use App\Models\TeacherSalaryPayment;
use App\Models\User;
use App\Services\CashBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OwnerTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->forgetInstance('settings');
        $this->travelTo(now()->setDate(2026, 10, 20)->setTime(12, 0));
        $user = User::factory()->create();
        $user->givePermissionTo('owner_transfers.manage');
        $this->actingAs($user);
    }

    private function income(float $amount = 1000, string $date = '2026-10-20', string $method = 'cash', string $status = 'confirmed'): Revenue
    {
        $category = RevenueCategory::firstOrCreate(['name' => 'Test fees'], ['payment_type' => 'one_time']);

        return Revenue::create([
            'revenue_category_id' => $category->id,
            'amount' => $amount,
            'paid_at' => $date,
            'payment_method' => $method,
            'payment_status' => $status,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'submission_id' => (string) Str::uuid(),
            'transferred_at' => '2026-10-20',
            'amount' => '300.00',
            'reference' => 'DEPOSIT-123',
            'notes' => 'Daily cash deposit',
        ], $overrides);
    }

    private function reportPermissions(): void
    {
        auth()->user()->givePermissionTo([
            'reports.view', 'reports.download', 'reports.daily_ledger.view',
            'reports.financial.view', 'reports.cash_transactions.view', 'reports.bank_transactions.view',
            'reports.expense.view', 'reports.outflows.view', 'dashboard.view',
        ]);
    }

    public function test_transfer_reduces_cash_without_creating_an_expense_or_affecting_profit(): void
    {
        $this->income();
        $this->post(route('owner-transfers.store'), $this->payload())
            ->assertSessionHasNoErrors()->assertRedirect(route('owner-transfers.index'));

        $this->assertDatabaseHas('owner_transfers', ['amount' => 300, 'created_by' => auth()->id()]);
        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('revenues', 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'owner_transfer.created']);
        $this->assertSame(700.0, app(CashBalanceService::class)->balanceOn('2026-10-20'));
        $this->get(route('owner-transfers.index'))->assertOk()->assertSee('Owner bank account')->assertSee('700.00');

        $this->reportPermissions();
        $this->get(route('dashboard', ['range' => 'today']))->assertOk()
            ->assertViewHas('totalExpenses', 0.0)->assertViewHas('netProfit', 1000.0);
        $this->get(route('reports.expense'))->assertOk()->assertDontSee('DEPOSIT-123');
    }

    public function test_multiple_daily_transfers_and_retries_deduct_cash_once_each(): void
    {
        $this->income(1000, '2026-10-19');
        $first = $this->payload(['transferred_at' => '2026-10-19', 'amount' => '400']);
        $this->post(route('owner-transfers.store'), $first)->assertSessionHasNoErrors();
        $this->post(route('owner-transfers.store'), $first)->assertSessionHasNoErrors();
        $this->post(route('owner-transfers.store'), $this->payload(['amount' => '200']))->assertSessionHasNoErrors();
        $this->post(route('owner-transfers.store'), $this->payload(['amount' => '100']))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('owner_transfers', 3);
        $this->assertDatabaseCount('audit_logs', 3);
        $this->assertSame(300.0, app(CashBalanceService::class)->balanceOn('2026-10-20'));
        $this->reportPermissions();
        $this->get(route('reports.daily_ledger'))->assertOk()
            ->assertViewHas('openingBalanceCash', 600.0)
            ->assertViewHas('totalTransfers', 300.0)
            ->assertViewHas('totalOut', 0.0)
            ->assertViewHas('closingBalanceCash', 300.0)
            ->assertViewHas('closingBalanceBank', 0.0);
    }

    public function test_available_cash_accounts_for_opening_balance_expenses_salary_and_bank_income(): void
    {
        OpeningBalance::create(['date' => '2026-10-18', 'amount' => 500]);
        $this->income(9999, '2026-10-17'); // Already included in the saved balance.
        $this->income(1000);
        $this->income(5000, '2026-10-20', 'bank_transfer');
        $this->income(7000, '2026-10-20', 'cheque', 'hold');
        $this->income(8000, '2026-10-20', 'cash', 'cancelled');
        Expense::create([
            'expense_category_id' => ExpenseCategory::create(['name' => 'Supplies'])->id,
            'expense_date' => '2026-10-20', 'payment_method' => 'cash', 'amount' => 100,
        ]);
        TeacherSalaryPayment::create([
            'teacher_id' => Teacher::create(['name' => 'Test teacher'])->id,
            'paid_at' => '2026-10-20', 'payment_method' => 'cash', 'amount' => 200, 'base_salary' => 200,
        ]);

        $this->assertSame(1200.0, app(CashBalanceService::class)->balanceOn('2026-10-20'));
        $this->post(route('owner-transfers.store'), $this->payload(['amount' => '1200']))->assertSessionHasNoErrors();
        $this->assertSame(0.0, app(CashBalanceService::class)->balanceOn('2026-10-20'));
        $this->post(route('owner-transfers.store'), $this->payload(['amount' => '0.01']))->assertSessionHasErrors('amount');
    }

    public function test_settings_opening_cash_is_available_for_transfer(): void
    {
        app('settings')->setMany([
            'opening_balance.as_of' => '2026-10-20',
            'opening_balance.cash' => '1500',
            'opening_balance.bank' => '9000',
        ], 'opening_balance');
        $this->post(route('owner-transfers.store'), $this->payload())->assertSessionHasNoErrors();
        $this->assertSame(1200.0, app(CashBalanceService::class)->balanceOn('2026-10-20'));
        $this->reportPermissions();
        $this->get(route('reports.daily_ledger'))->assertOk()
            ->assertViewHas('openingBalanceCash', 1500.0)->assertViewHas('closingBalanceCash', 1200.0);
        $this->get(route('reports.financial', ['method' => 'cash']))->assertOk()
            ->assertViewHas('days', fn ($days) => $days[0]['opening'] === 1500.0 && $days[0]['closing'] === 1200.0);
    }

    public function test_transfer_cannot_be_added_before_an_existing_cash_snapshot(): void
    {
        OpeningBalance::create(['date' => '2026-10-19', 'amount' => 1000]);
        $this->post(route('owner-transfers.store'), $this->payload(['transferred_at' => '2026-10-19']))
            ->assertSessionHasErrors('transferred_at');
        $this->assertDatabaseCount('owner_transfers', 0);
    }

    public function test_transfer_cannot_be_added_before_the_system_opening_date(): void
    {
        $this->income(1000, '2026-10-19');
        app('settings')->setMany([
            'opening_balance.as_of' => '2026-10-20',
            'opening_balance.cash' => '1500',
        ], 'opening_balance');
        $this->post(route('owner-transfers.store'), $this->payload(['transferred_at' => '2026-10-19']))
            ->assertSessionHasErrors('transferred_at');
        $this->assertDatabaseCount('owner_transfers', 0);
    }

    public function test_backdated_transfer_cannot_spend_cash_already_transferred_later(): void
    {
        $this->income(1000, '2026-10-19');
        $this->post(route('owner-transfers.store'), $this->payload(['amount' => '800']))->assertSessionHasNoErrors();
        $this->post(route('owner-transfers.store'), $this->payload(['transferred_at' => '2026-10-19']))->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('owner_transfers', 1);
    }

    public function test_refunded_cash_is_unavailable_for_transfer(): void
    {
        $revenue = $this->income();
        RevenueAdjustment::create(['revenue_id' => $revenue->id, 'type' => 'refund', 'amount' => 800]);
        $this->post(route('owner-transfers.store'), $this->payload())->assertSessionHasErrors('amount');
        $this->assertSame(200.0, app(CashBalanceService::class)->balanceOn('2026-10-20'));
        $this->assertDatabaseCount('owner_transfers', 0);
    }

    public static function invalidTransfers(): array
    {
        return [
            'zero' => ['amount', '0'],
            'negative' => ['amount', '-1'],
            'too many decimals' => ['amount', '1.001'],
            'invalid amount' => ['amount', 'invalid'],
            'over available cash' => ['amount', '1000.01'],
            'overflow' => ['amount', '10000000000'],
            'future date' => ['transferred_at', '2026-10-21'],
            'invalid date' => ['transferred_at', 'invalid'],
            'missing date' => ['transferred_at', ''],
            'missing token' => ['submission_id', ''],
            'long reference' => ['reference', str_repeat('x', 121)],
            'long notes' => ['notes', str_repeat('x', 2001)],
        ];
    }

    #[DataProvider('invalidTransfers')]
    public function test_invalid_transfers_are_rejected(string $field, string $value): void
    {
        $this->income();
        $this->post(route('owner-transfers.store'), $this->payload([$field => $value]))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('owner_transfers', 0);
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_permissions_protect_page_submission_and_mobile_and_desktop_links(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('owner-transfers.index'))->assertForbidden();
        $this->post(route('owner-transfers.store'), $this->payload())->assertForbidden();
        $this->get(route('profile.edit'))->assertDontSee('href="'.route('owner-transfers.index').'"', false);
        $user->givePermissionTo('owner_transfers.manage');
        $response = $this->get(route('owner-transfers.index'))->assertOk();
        $this->assertSame(2, substr_count($response->getContent(), 'href="'.route('owner-transfers.index').'"'));
        $this->assertSame('owner-transfers.index', $user->landingRoute());
    }

    public function test_administrators_can_use_the_transfer_option(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin', 'web'));
        $this->actingAs($admin)->get(route('owner-transfers.index'))->assertOk();
    }

    public function test_cash_reports_show_transfer_separately_and_bank_report_excludes_it(): void
    {
        $this->income();
        $this->post(route('owner-transfers.store'), $this->payload())->assertSessionHasNoErrors();
        $this->reportPermissions();
        $this->get(route('reports.daily_ledger'))->assertOk()
            ->assertViewHas('totalTransfers', 300.0)->assertViewHas('totalOut', 0.0)
            ->assertViewHas('closingBalance', 700.0)->assertViewHas('closingBalanceCash', 700.0)
            ->assertViewHas('expenses', fn ($expenses) => $expenses->isEmpty())
            ->assertSee('Transfers to Owner bank account');
        $this->get(route('reports.cash_transactions'))->assertOk()->assertViewHas('totalOut', 300.0)
            ->assertSee('DEPOSIT-123')->assertSee('Transfer');
        $this->get(route('reports.bank_transactions'))->assertOk()->assertViewHas('totalOut', 0.0)
            ->assertViewHas('totalIn', 0.0)->assertDontSee('DEPOSIT-123');
        $this->get(route('reports.daily_ledger', ['method' => 'bank']))->assertOk()
            ->assertViewHas('totalTransfers', 0.0)->assertViewHas('closingBalanceBank', 0.0);
        $this->get(route('reports.outflows'))->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->where('type', 'Transfer')->sum('amount') === 300.0)
            ->assertSee('Owner bank account');
        $this->get(route('reports.outflows', ['method' => 'bank']))->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->where('type', 'Transfer')->isEmpty());
        $this->get(route('reports.daily_ledger', ['from' => '2026-10-19', 'to' => '2026-10-19']))->assertOk()
            ->assertViewHas('totalTransfers', 0.0);
    }

    public function test_financial_report_separates_transfers_and_carries_balances_between_days(): void
    {
        $this->income(1000, '2026-10-19');
        $this->post(route('owner-transfers.store'), $this->payload(['transferred_at' => '2026-10-19']))->assertSessionHasNoErrors();
        $this->post(route('owner-transfers.store'), $this->payload(['amount' => '200']))->assertSessionHasNoErrors();
        $this->reportPermissions();
        foreach ([false, true] as $daily) {
            $this->get(route('reports.financial', ['from' => '2026-10-19', 'to' => '2026-10-20', 'daily' => $daily]))
                ->assertOk()->assertViewHas('totalExpense', 0)->assertViewHas('netProfit', 1000)
                ->assertViewHas('days', fn ($days) => (float) end($days)['closing'] === 500.0 && collect($days)->sum('transfer_total') === 500.0);
        }
        $this->get(route('reports.financial', ['from' => '2026-10-20']))->assertOk()
            ->assertViewHas('days', fn ($days) => $days[0]['opening'] === 700.0 && $days[0]['closing'] === 500.0);
        $this->get(route('reports.financial', ['method' => 'bank']))->assertOk()
            ->assertViewHas('days', fn ($days) => $days[0]['transfer_total'] === 0.0);
    }

    public static function exportReports(): array
    {
        return [
            ['reports.daily_ledger'], ['reports.financial'], ['reports.cash_transactions'], ['reports.outflows'],
        ];
    }

    #[DataProvider('exportReports')]
    public function test_pdf_and_excel_exports_include_transfers(string $route): void
    {
        $this->income();
        $this->post(route('owner-transfers.store'), $this->payload())->assertSessionHasNoErrors();
        $this->reportPermissions();
        $pdf = $this->get(route($route, ['pdf' => 1]))->assertOk()->assertDownload();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());

        $response = $this->get(route($route, ['excel' => 1]))->assertOk()->assertDownload();
        ob_start();
        $response->baseResponse->sendContent();
        $binary = ob_get_clean();
        $path = tempnam(sys_get_temp_dir(), 'owner-transfer-export-');
        try {
            file_put_contents($path, $binary);
            $sheet = IOFactory::load($path);
            $cells = json_encode($sheet->getActiveSheet()->toArray());
            $this->assertStringContainsString('Owner bank account', $cells);
            $this->assertStringContainsString('300', $cells);
            $sheet->disconnectWorksheets();
        } finally {
            unlink($path);
        }
    }
}
