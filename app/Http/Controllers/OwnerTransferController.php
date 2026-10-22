<?php

namespace App\Http\Controllers;

use App\Models\OpeningBalance;
use App\Models\OwnerTransfer;
use App\Services\AuditLogger;
use App\Services\CashBalanceService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OwnerTransferController extends Controller
{
    public function index(CashBalanceService $cash): View
    {
        return view('owner-transfers.index', [
            'cashInHand' => $cash->balanceOn(now()->toDateString()),
            'transfers' => OwnerTransfer::query()->with('creator')->orderByDesc('transferred_at')->orderByDesc('id')->paginate(25),
            'submissionId' => (string) Str::uuid(),
        ]);
    }

    public function store(Request $request, CashBalanceService $cash, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'submission_id' => ['required', 'uuid'],
            'transferred_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            Cache::lock('owner-transfers.store', 30)->block(5, function () use ($data, $request, $cash, $audit) {
                DB::transaction(function () use ($data, $request, $cash, $audit) {
                    // Retrying the same form must never deduct cash a second time.
                    if (OwnerTransfer::where('submission_id', $data['submission_id'])->exists()) {
                        return;
                    }

                    $snapshotDate = OpeningBalance::max('date');
                    if ($snapshotDate && $data['transferred_at'] <= $snapshotDate) {
                        throw ValidationException::withMessages([
                            'transferred_at' => 'Choose a date after the latest saved cash balance ('.$snapshotDate.').',
                        ]);
                    }
                    $openingDate = (string) app('settings')->get('opening_balance.as_of', '');
                    if ($openingDate !== '' && $data['transferred_at'] < $openingDate) {
                        throw ValidationException::withMessages([
                            'transferred_at' => 'Choose a date on or after the opening balance date ('.$openingDate.').',
                        ]);
                    }

                    $available = $cash->balanceOn($data['transferred_at']);
                    $available = min($available, $cash->balanceOn(now()->toDateString()));
                    if ((float) $data['amount'] > $available) {
                        throw ValidationException::withMessages([
                            'amount' => 'The transfer exceeds cash in hand on this date (Rs '.number_format($available, 2).').',
                        ]);
                    }

                    $transfer = OwnerTransfer::create($data + ['created_by' => $request->user()->id]);
                    $audit->log('owner_transfer.created', $transfer, 'Hand cash transferred to '.OwnerTransfer::DESTINATION, [
                        'amount' => $transfer->amount,
                        'date' => $data['transferred_at'],
                        'destination' => OwnerTransfer::DESTINATION,
                    ]);
                });
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['amount' => 'Another transfer is being saved. Please try again.']);
        }

        return redirect()->route('owner-transfers.index')->with('success', 'Transfer to Owner bank account recorded. Cash in hand has been reduced.');
    }
}
