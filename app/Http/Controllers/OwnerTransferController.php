<?php

namespace App\Http\Controllers;

use App\Models\OwnerTransfer;
use App\Services\CashBalanceService;
use App\Services\OwnerTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OwnerTransferController extends Controller
{
    public function index(CashBalanceService $cash): View
    {
        return view('owner-transfers.index', [
            'cashInHand' => $cash->balanceOn(now()->toDateString()),
            'transfers' => OwnerTransfer::query()->with(['creator', 'deleter', 'returns.creator'])->withSum('returns', 'amount')
                ->orderByDesc('transferred_at')->orderByDesc('id')->paginate(25),
            'submissionId' => (string) Str::uuid(),
        ]);
    }

    public function store(Request $request, OwnerTransferService $transfers): RedirectResponse
    {
        $data = $request->validate($this->rules() + ['submission_id' => ['required', 'uuid']]);
        $transfers->store($data, $request->user());

        return redirect()->route('owner-transfers.index')->with('success', 'Transfer to Owner bank account recorded. Cash in hand has been reduced.');
    }

    public function update(Request $request, OwnerTransfer $transfer, OwnerTransferService $transfers): RedirectResponse
    {
        $data = $request->validate($this->rules() + ['version' => ['required', 'integer', 'min:0']]);
        $transfers->update($transfer, $data);

        return redirect()->route('owner-transfers.index')->with('success', 'Transfer updated. Cash and transfer balances have been recalculated.');
    }

    public function destroy(Request $request, OwnerTransfer $transfer, OwnerTransferService $transfers): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000'], 'version' => ['required', 'integer', 'min:0']]);
        $transfers->delete($transfer, $data, $request->user());

        return redirect()->route('owner-transfers.index')->with('success', 'Transfer marked as deleted. Its history and reason remain visible; its cash movements are excluded from balances.');
    }

    public function returnAmount(Request $request, OwnerTransfer $transfer, OwnerTransferService $transfers): RedirectResponse
    {
        $data = $request->validate([
            'submission_id' => ['required', 'uuid'], 'version' => ['required', 'integer', 'min:0'],
            'returned_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $transfers->returnAmount($transfer, $data, $request->user());

        return redirect()->route('owner-transfers.index')->with('success', 'Return recorded. Cash in hand has increased and the net transferred amount has decreased.');
    }

    private function rules(): array
    {
        return [
            'transferred_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'destination' => ['nullable', 'string', 'max:190'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
