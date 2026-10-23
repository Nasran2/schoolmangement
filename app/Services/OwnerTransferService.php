<?php

namespace App\Services;

use App\Models\OpeningBalance;
use App\Models\OwnerTransfer;
use App\Models\OwnerTransferReturn;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OwnerTransferService
{
    public function __construct(private CashBalanceService $cash, private AuditLogger $audit) {}

    public function store(array $data, User $user): void
    {
        $this->locked(function () use ($data, $user) {
            if (OwnerTransfer::where('submission_id', $data['submission_id'])->exists()) {
                return;
            }
            $this->assertOpenDate($data['transferred_at'], 'transferred_at');
            $transfer = OwnerTransfer::create($data + ['created_by' => $user->id]);
            $this->assertCashAvailable($data['transferred_at']);
            $this->audit->log('owner_transfer.created', $transfer, 'Hand cash transferred to '.$transfer->destination, $transfer->toArray());
        });
    }

    public function update(OwnerTransfer $transfer, array $data): void
    {
        $this->locked(function () use ($transfer, $data) {
            $transfer = OwnerTransfer::lockForUpdate()->findOrFail($transfer->id);
            $this->assertEditable($transfer, $data['version']);
            $before = $transfer->toArray();
            $oldDate = $transfer->transferred_at->toDateString();
            $moneyChanged = (float) $transfer->amount !== (float) $data['amount'] || $oldDate !== $data['transferred_at'];
            if ($moneyChanged) {
                $this->assertOpenDate($oldDate, 'transferred_at');
                $this->assertOpenDate($data['transferred_at'], 'transferred_at');
            }
            $returned = (float) $transfer->returns()->sum('amount');
            if ((float) $data['amount'] < $returned) {
                throw ValidationException::withMessages(['amount' => 'The transfer cannot be less than the amount already returned (Rs '.number_format($returned, 2).').']);
            }
            $firstReturn = $transfer->returns()->min('returned_at');
            if ($firstReturn && $data['transferred_at'] > $firstReturn) {
                throw ValidationException::withMessages(['transferred_at' => 'The transfer date must be on or before its first return ('.$firstReturn.').']);
            }
            $transfer->fill($data);
            $transfer->version++;
            $transfer->save();
            if ($moneyChanged) {
                $this->assertCashAvailable(min($oldDate, $data['transferred_at']));
            }
            $this->audit->log('owner_transfer.updated', $transfer, 'Owner bank transfer edited', ['before' => $before, 'after' => $transfer->toArray()]);
        });
    }

    public function delete(OwnerTransfer $transfer, array $data, User $user): void
    {
        $this->locked(function () use ($transfer, $data, $user) {
            $transfer = OwnerTransfer::lockForUpdate()->findOrFail($transfer->id);
            if ($transfer->deleted_at) {
                return;
            }
            $this->assertEditable($transfer, $data['version']);
            $this->assertOpenDate($transfer->transferred_at->toDateString(), 'reason');
            $transfer->forceFill(['deleted_at' => now(), 'deleted_by' => $user->id, 'deletion_reason' => $data['reason'], 'version' => $transfer->version + 1])->save();
            $this->audit->log('owner_transfer.deleted', $transfer, 'Owner bank transfer deleted: '.$data['reason'], [
                'original_amount' => $transfer->amount, 'returned_amount' => $transfer->returned_amount,
                'reason' => $data['reason'], 'destination' => $transfer->destination,
            ]);
        });
    }

    public function returnAmount(OwnerTransfer $transfer, array $data, User $user): void
    {
        $this->locked(function () use ($transfer, $data, $user) {
            $transfer = OwnerTransfer::lockForUpdate()->findOrFail($transfer->id);
            $existing = OwnerTransferReturn::where('submission_id', $data['submission_id'])->first();
            if ($existing) {
                if ($existing->owner_transfer_id !== $transfer->id) {
                    throw ValidationException::withMessages(['submission_id' => 'This return form was already used for another transfer. Reload and try again.']);
                }

                return;
            }
            $this->assertEditable($transfer, $data['version']);
            $this->assertOpenDate($data['returned_at'], 'returned_at');
            if ($data['returned_at'] < $transfer->transferred_at->toDateString()) {
                throw ValidationException::withMessages(['returned_at' => 'A return cannot be dated before its transfer.']);
            }
            $remaining = round((float) $transfer->amount - (float) $transfer->returns()->sum('amount'), 2);
            if ((float) $data['amount'] > $remaining) {
                throw ValidationException::withMessages(['amount' => 'The return exceeds the remaining transferred amount (Rs '.number_format($remaining, 2).').']);
            }
            $return = $transfer->returns()->create($data + ['created_by' => $user->id]);
            $transfer->increment('version');
            $this->audit->log('owner_transfer.returned', $return, $return->description, [
                'transfer_id' => $transfer->id, 'amount' => $return->amount, 'returned_at' => $data['returned_at'], 'destination' => $transfer->destination,
            ]);
        });
    }

    private function assertEditable(OwnerTransfer $transfer, int $version): void
    {
        if ($transfer->deleted_at) {
            throw ValidationException::withMessages(['transfer' => 'This transfer has been deleted.']);
        }
        if ($transfer->version !== $version) {
            throw ValidationException::withMessages(['transfer' => 'This transfer changed after the page was opened. Reload it before continuing.']);
        }
    }

    private function assertOpenDate(string $date, string $field): void
    {
        $snapshotDate = OpeningBalance::max('date');
        if ($snapshotDate && $date <= $snapshotDate) {
            throw ValidationException::withMessages([$field => 'Choose a date after the latest saved cash balance ('.$snapshotDate.'). Earlier cash movements are already included in that balance.']);
        }
        $openingDate = (string) app('settings')->get('opening_balance.as_of', '');
        if ($openingDate !== '' && $date < $openingDate) {
            throw ValidationException::withMessages([$field => 'Choose a date on or after the opening balance date ('.$openingDate.').']);
        }
    }

    private function assertCashAvailable(string $from): void
    {
        $today = now()->toDateString();
        $dates = collect([$from, $today]);
        // Check intervening dates too, so a backdated edit cannot spend cash that was unavailable then.
        foreach (['revenues' => 'paid_at', 'expenses' => 'expense_date', 'teacher_salary_payments' => 'paid_at',
            'owner_transfers' => 'transferred_at', 'owner_transfer_returns' => 'returned_at'] as $table => $column) {
            $dates = $dates->concat(DB::table($table)->whereDate($column, '>=', $from)->whereDate($column, '<=', $today)
                ->selectRaw('DATE('.$column.') as cash_date')->distinct()->pluck('cash_date'));
        }
        foreach ($dates->unique()->sort() as $date) {
            if ($this->cash->balanceOn($date) < 0) {
                throw ValidationException::withMessages(['amount' => 'This transfer would exceed cash in hand on '.$date.'. Reduce the amount or choose another date.']);
            }
        }
    }

    private function locked(callable $callback): void
    {
        try {
            Cache::lock('owner-transfers.store', 30)->block(5, fn () => DB::transaction($callback));
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['transfer' => 'Another transfer is being saved. Please try again.']);
        }
    }
}
