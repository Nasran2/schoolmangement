<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\OpeningBalance;
use App\Models\OwnerTransfer;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\User;
use App\Services\CashBalanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OwnerTransferCorrectionsTest extends TestCase
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

    private function income(float $amount = 10000, string $date = '2026-10-19'): void
    {
        $category = RevenueCategory::firstOrCreate(['name' => 'Test fees'], ['payment_type' => 'one_time']);
        Revenue::create(['revenue_category_id' => $category->id, 'amount' => $amount, 'paid_at' => $date,
            'payment_method' => 'cash', 'payment_status' => 'confirmed']);
    }

    private function transfer(float $amount = 6000, string $date = '2026-10-20'): OwnerTransfer
    {
        $this->post(route('owner-transfers.store'), ['submission_id' => (string) Str::uuid(), 'transferred_at' => $date,
            'amount' => $amount, 'destination' => 'Owner — Test Bank • 1234', 'reference' => 'BANK-REF', 'notes' => 'Cash deposit'])->assertSessionHasNoErrors();

        return OwnerTransfer::latest('id')->firstOrFail();
    }

    private function returnPayload(OwnerTransfer $transfer, array $extra = []): array
    {
        return $extra + ['submission_id' => (string) Str::uuid(), 'version' => $transfer->fresh()->version,
            'amount' => 2000, 'returned_at' => '2026-10-22', 'notes' => 'Deposit difference returned'];
    }

    private function returnCash(OwnerTransfer $transfer, array $extra = []): void
    {
        $this->post(route('owner-transfers.returns.store', $transfer), $this->returnPayload($transfer, $extra))->assertSessionHasNoErrors();
    }

    private function editPayload(OwnerTransfer $transfer, array $extra = []): array
    {
        return $extra + ['version' => $transfer->fresh()->version, 'amount' => $transfer->amount,
            'transferred_at' => $transfer->transferred_at->toDateString(), 'destination' => $transfer->destination,
            'reference' => $transfer->reference, 'notes' => $transfer->notes];
    }

    public function test_return_adds_cash_on_its_own_date_reduces_net_transfer_and_does_not_create_income_or_expense(): void
    {
        $this->income();
        $transfer = $this->transfer();
        $this->returnCash($transfer);
        $cash = app(CashBalanceService::class);
        $this->assertSame(4000.0, $cash->balanceOn('2026-10-20'));
        $this->assertSame(4000.0, $cash->openingOn('2026-10-22'));
        $this->assertSame(6000.0, $cash->balanceOn('2026-10-22'));
        $this->assertSame(4000.0, $transfer->fresh()->net_amount);
        $this->assertDatabaseCount('revenues', 1);
        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'owner_transfer.returned']);
        $this->get(route('owner-transfers.index'))->assertOk()->assertSee('Return amount')->assertSee('6,000.00')->assertSee('2,000.00')->assertSee('4,000.00')->assertSee('Owner — Test Bank • 1234');
        $this->get(route('reports.daily_ledger', ['from' => '2026-10-22']))->assertOk()
            ->assertViewHas('openingBalanceCash', 4000.0)->assertViewHas('grossTransfers', 0.0)
            ->assertViewHas('totalTransferReturns', 2000.0)->assertViewHas('totalTransfers', -2000.0)
            ->assertViewHas('closingBalanceCash', 6000.0)->assertViewHas('closingBalanceBank', 0.0)
            ->assertViewHas('totalIn', 0.0)->assertViewHas('totalOut', 0.0)
            ->assertSee('Return amount from cash transfer to owner account')->assertSee('Owner — Test Bank • 1234');
    }

    public function test_multiple_returns_and_retries_are_idempotent_and_cannot_exceed_the_transfer(): void
    {
        $this->income();
        $transfer = $this->transfer();
        $payload = $this->returnPayload($transfer);
        foreach ([1, 2] as $retry) {
            $this->post(route('owner-transfers.returns.store', $transfer), $payload)->assertSessionHasNoErrors();
        }
        $this->returnCash($transfer, ['amount' => 4000]);
        $this->assertDatabaseCount('owner_transfer_returns', 2);
        $this->assertSame(0.0, $transfer->fresh()->net_amount);
        $this->assertSame(10000.0, app(CashBalanceService::class)->balanceOn('2026-10-23'));
        $this->post(route('owner-transfers.returns.store', $transfer), $this->returnPayload($transfer, ['amount' => 0.01]))->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('owner_transfer_returns', 2);
        $this->get(route('owner-transfers.index'))->assertOk()->assertDontSee('data-transfer-dialog="return-transfer-'.$transfer->id.'"', false);
    }

    public function test_edit_recalculates_cash_and_preserves_return_history_and_audit(): void
    {
        $this->income();
        $transfer = $this->transfer();
        $this->returnCash($transfer);
        $this->put(route('owner-transfers.update', $transfer), $this->editPayload($transfer, ['amount' => 6500,
            'destination' => 'Owner — Correct Bank • 5678', 'reference' => 'CORRECTED']))->assertSessionHasNoErrors();
        $this->assertSame(5500.0, app(CashBalanceService::class)->balanceOn('2026-10-23'));
        $this->assertSame(4500.0, $transfer->fresh()->net_amount);
        $this->assertDatabaseCount('owner_transfer_returns', 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'owner_transfer.updated']);
        $this->get(route('reports.daily_ledger', ['from' => '2026-10-19', 'to' => '2026-10-23']))->assertOk()
            ->assertSee('Owner — Correct Bank • 5678')->assertSee('CORRECTED')->assertViewHas('totalTransfers', 4500.0);
        $this->put(route('owner-transfers.update', $transfer), $this->editPayload($transfer, ['amount' => 1999]))->assertSessionHasErrors('amount');
        $this->put(route('owner-transfers.update', $transfer), $this->editPayload($transfer, ['transferred_at' => '2026-10-23']))->assertSessionHasErrors('transferred_at');
    }

    public function test_deleted_transfer_and_returns_stay_visible_with_reason_but_have_zero_cash_effect(): void
    {
        $this->income();
        $transfer = $this->transfer();
        $this->returnCash($transfer);
        $this->delete(route('owner-transfers.destroy', $transfer), ['version' => $transfer->fresh()->version])->assertSessionHasErrors('reason');
        $this->assertNull($transfer->fresh()->deleted_at);
        $payload = ['version' => $transfer->fresh()->version, 'reason' => 'Duplicate deposit entry'];
        foreach ([1, 2] as $retry) {
            $this->delete(route('owner-transfers.destroy', $transfer), $payload)->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('owner_transfers', 1);
        $this->assertDatabaseCount('owner_transfer_returns', 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'owner_transfer.deleted']);
        $this->assertSame(10000.0, app(CashBalanceService::class)->balanceOn('2026-10-23'));
        $this->get(route('owner-transfers.index'))->assertOk()->assertSee('Deleted')->assertSee('Duplicate deposit entry')
            ->assertSee('owner-transfer-deleted')->assertDontSee('data-transfer-dialog="edit-transfer-'.$transfer->id.'"', false);
        $this->get(route('reports.daily_ledger', ['from' => '2026-10-19', 'to' => '2026-10-23']))->assertOk()
            ->assertViewHas('totalTransfers', 0.0)->assertViewHas('totalTransferReturns', 0.0)->assertViewHas('closingBalanceCash', 10000.0)
            ->assertSee('Deleted')->assertSee('Duplicate deposit entry');
        $this->get(route('reports.cash_transactions'))->assertOk()->assertViewHas('totalOut', 0.0)->assertViewHas('closingBalance', 10000.0);
        $this->get(route('reports.outflows'))->assertOk()->assertViewHas('summary', fn ($summary) => $summary['total_amount'] === 0.0);
        $this->put(route('owner-transfers.update', $transfer), $this->editPayload($transfer))->assertSessionHasErrors('transfer');
        $this->post(route('owner-transfers.returns.store', $transfer), $this->returnPayload($transfer))->assertSessionHasErrors('transfer');
    }

    public static function invalidReturns(): array
    {
        return [['amount', 0], ['amount', -1], ['amount', 6000.01], ['amount', '1.001'], ['amount', 'invalid'],
            ['returned_at', '2026-10-19'], ['returned_at', '2026-10-24'], ['returned_at', 'invalid'],
            ['submission_id', ''], ['version', -1], ['notes', str_repeat('x', 2001)]];
    }

    #[DataProvider('invalidReturns')]
    public function test_invalid_returns_leave_balances_unchanged(string $field, mixed $value): void
    {
        $this->income();
        $transfer = $this->transfer();
        $this->post(route('owner-transfers.returns.store', $transfer), $this->returnPayload($transfer, [$field => $value]))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('owner_transfer_returns', 0);
        $this->assertSame(4000.0, app(CashBalanceService::class)->balanceOn('2026-10-23'));
    }

    public function test_stale_edits_and_returns_and_cross_transfer_retry_tokens_are_rejected(): void
    {
        $this->income();
        $transfer = $this->transfer();
        $staleEdit = $this->editPayload($transfer);
        $return = $this->returnPayload($transfer);
        $this->post(route('owner-transfers.returns.store', $transfer), $return)->assertSessionHasNoErrors();
        $this->put(route('owner-transfers.update', $transfer), $staleEdit)->assertSessionHasErrors('transfer');
        $this->post(route('owner-transfers.returns.store', $transfer), $this->returnPayload($transfer, ['version' => 0]))->assertSessionHasErrors('transfer');
        $other = $this->transfer(1000);
        $this->post(route('owner-transfers.returns.store', $other), $return)->assertSessionHasErrors('submission_id');
        $this->assertDatabaseCount('owner_transfer_returns', 1);
    }

    public function test_cash_returned_can_be_transferred_again_and_amount_edits_cannot_overdraw_intervening_dates(): void
    {
        $this->income();
        $transfer = $this->transfer();
        $this->returnCash($transfer);
        $this->transfer(6000, '2026-10-23');
        $this->assertSame(0.0, app(CashBalanceService::class)->balanceOn('2026-10-23'));
        $this->put(route('owner-transfers.update', $transfer), $this->editPayload($transfer, ['amount' => 6000.01]))->assertSessionHasErrors('amount');
        $this->assertSame(6000.0, (float) $transfer->fresh()->amount);
    }

    public function test_backdated_transfer_cannot_use_a_later_return_to_cover_an_intervening_cash_shortage(): void
    {
        $this->income();
        $this->income(8000, '2026-10-23');
        Expense::create(['expense_category_id' => ExpenseCategory::create(['name' => 'Supplies'])->id,
            'expense_date' => '2026-10-21', 'payment_method' => 'cash', 'amount' => 8000]);
        $this->post(route('owner-transfers.store'), ['submission_id' => (string) Str::uuid(),
            'transferred_at' => '2026-10-20', 'amount' => 3000])->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('owner_transfers', 0);
    }

    public function test_snapshots_protect_included_cash_movements_but_allow_later_returns_and_metadata_corrections(): void
    {
        $this->income();
        $transfer = $this->transfer();
        OpeningBalance::create(['date' => '2026-10-21', 'amount' => 4000]);
        $this->returnCash($transfer);
        $this->assertSame(6000.0, app(CashBalanceService::class)->balanceOn('2026-10-23'));
        $this->put(route('owner-transfers.update', $transfer), $this->editPayload($transfer, ['amount' => 5000]))->assertSessionHasErrors('transferred_at');
        $this->delete(route('owner-transfers.destroy', $transfer), ['version' => $transfer->fresh()->version, 'reason' => 'Correction'])->assertSessionHasErrors('reason');
        $this->put(route('owner-transfers.update', $transfer), $this->editPayload($transfer, ['destination' => 'Corrected bank name']))->assertSessionHasNoErrors();
        $this->assertSame(6000.0, app(CashBalanceService::class)->balanceOn('2026-10-23'));
    }

    public function test_all_reports_agree_on_dated_net_transfers_without_affecting_profit_or_school_bank(): void
    {
        $this->income();
        $transfer = $this->transfer();
        $this->returnCash($transfer);
        $range = ['from' => '2026-10-19', 'to' => '2026-10-23'];
        $this->get(route('reports.daily_ledger', $range))->assertOk()->assertViewHas('grossTransfers', 6000.0)
            ->assertViewHas('totalTransferReturns', 2000.0)->assertViewHas('totalTransfers', 4000.0)
            ->assertViewHas('closingBalanceCash', 6000.0)->assertViewHas('closingBalanceBank', 0.0);
        foreach ([0, 1] as $daily) {
            $this->get(route('reports.financial', $range + ['daily' => $daily]))->assertOk()
                ->assertViewHas('netProfit', 10000)->assertViewHas('totalExpense', 0)
                ->assertViewHas('days', fn ($days) => collect($days)->sum('transfer_total') === 4000.0 && (float) end($days)['closing'] === 6000.0);
        }
        $this->get(route('reports.cash_transactions', $range))->assertOk()
            ->assertViewHas('totalIn', 12000.0)->assertViewHas('totalOut', 6000.0)->assertViewHas('closingBalance', 6000.0)->assertSee('Owner Transfer Return');
        $this->get(route('reports.outflows', $range))->assertOk()->assertViewHas('summary', fn ($summary) => $summary['total_amount'] === 4000.0);
        $this->get(route('reports.bank_transactions', $range))->assertOk()->assertViewHas('totalIn', 0.0)->assertViewHas('totalOut', 0.0)->assertDontSee('BANK-REF');
        $this->get(route('reports.daily_ledger', $range + ['method' => 'bank']))->assertOk()->assertViewHas('totalTransfers', 0.0)->assertViewHas('totalTransferReturns', 0.0);
    }

    public function test_guarded_migration_can_follow_a_manual_schema_install_without_resetting_records(): void
    {
        $this->income();
        $transfer = $this->transfer();
        $this->returnCash($transfer);
        $migration = require database_path('migrations/2026_10_23_000001_add_owner_transfer_corrections_and_returns.php');
        $migration->up();
        $this->assertDatabaseCount('owner_transfers', 1);
        $this->assertDatabaseCount('owner_transfer_returns', 1);
        $this->assertSame(4000.0, $transfer->fresh()->net_amount);
    }

    public function test_new_routes_require_transfer_management_permission(): void
    {
        $this->income();
        $transfer = $this->transfer();
        $edit = $this->editPayload($transfer);
        $return = $this->returnPayload($transfer);
        $this->actingAs(User::factory()->create());
        $this->put(route('owner-transfers.update', $transfer), $edit)->assertForbidden();
        $this->delete(route('owner-transfers.destroy', $transfer), ['version' => 0, 'reason' => 'Test'])->assertForbidden();
        $this->post(route('owner-transfers.returns.store', $transfer), $return)->assertForbidden();
        $this->assertDatabaseCount('owner_transfer_returns', 0);
        $this->assertNull($transfer->fresh()->deleted_at);
    }

    public function test_daily_ledger_pdf_and_excel_keep_return_reference_destination_and_separate_cash_in(): void
    {
        $this->income();
        $transfer = $this->transfer();
        $this->returnCash($transfer);
        $range = ['from' => '2026-10-22', 'to' => '2026-10-22'];
        $pdf = $this->get(route('reports.daily_ledger', $range + ['pdf' => 1]))->assertOk()->assertDownload();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $response = $this->get(route('reports.daily_ledger', $range + ['excel' => 1]))->assertOk()->assertDownload();
        ob_start();
        $response->baseResponse->sendContent();
        $binary = ob_get_clean();
        $path = tempnam(sys_get_temp_dir(), 'owner-return-export-');
        try {
            file_put_contents($path, $binary);
            $sheet = IOFactory::load($path);
            $rows = collect($sheet->getActiveSheet()->toArray());
            $return = $rows->first(fn ($row) => $row[0] === 'Transfer return');
            $this->assertNotNull($return);
            $this->assertStringContainsString('Owner — Test Bank', $return[5]);
            $this->assertSame(2000.0, (float) $return[9]);
            $this->assertSame(0.0, (float) $return[10]);
            $sheet->disconnectWorksheets();
        } finally {
            unlink($path);
        }
    }
}
