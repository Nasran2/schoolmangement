@php($ownerTransferReturns = $ownerTransferReturns ?? collect())
@if($ownerTransfers->isNotEmpty() || $ownerTransferReturns->isNotEmpty())
    <div class="mt-6" style="margin-top:20px;">
        <h3 class="text-lg font-bold text-gray-900 mb-3">Transfers to Owner bank account</h3>
        <p class="text-sm text-gray-600 mb-3">Hand cash transferred to the owner and amounts returned to cash. Excluded from revenue, expenses and profit.</p>
        @if($ownerTransfers->isNotEmpty())
            <table class="min-w-full text-sm" style="width:100%;background:#eef2ff;">
                <thead><tr style="background:#e0e7ff;color:#3730a3;">
                    <th class="px-4 py-3" style="text-align:left;">Date</th>
                    <th class="px-4 py-3" style="text-align:left;">Reference</th>
                    <th class="px-4 py-3" style="text-align:left;">Owner bank / account</th>
                    <th class="px-4 py-3" style="text-align:left;">Details</th>
                    <th class="px-4 py-3" style="text-align:right;">Transferred (Rs)</th>
                </tr></thead>
                <tbody>
                    @foreach($ownerTransfers as $transfer)
                        <tr style="background:{{ $transfer->deleted_at ? '#fff1f2' : '#eef2ff' }};color:{{ $transfer->deleted_at ? '#b91c1c' : '#3730a3' }};">
                            <td class="px-4 py-3">{{ $transfer->transferred_at->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">{{ $transfer->reference ?: 'OT-'.$transfer->id }}</td>
                            <td class="px-4 py-3">{{ $transfer->destination }}</td>
                            <td class="px-4 py-3">
                                @if($transfer->deleted_at)<strong>Deleted</strong> — {{ $transfer->deletion_reason }}<br>Original: Rs {{ number_format($transfer->amount, 2) }}
                                @else {{ $transfer->notes ?: 'Cash transferred to owner account' }} @endif
                            </td>
                            <td class="px-4 py-3 font-mono" style="text-align:right;font-weight:bold;">{{ number_format($transfer->effective_amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        @if($ownerTransferReturns->isNotEmpty())
            <h4 class="font-semibold mt-4 mb-2" style="margin-top:16px;color:#065f46;">Returned to cash from owner transfers</h4>
            <table class="min-w-full text-sm" style="width:100%;background:#ecfdf5;">
                <thead><tr style="background:#d1fae5;color:#065f46;">
                    <th class="px-4 py-3" style="text-align:left;">Return date</th>
                    <th class="px-4 py-3" style="text-align:left;">Reference</th>
                    <th class="px-4 py-3" style="text-align:left;">Details</th>
                    <th class="px-4 py-3" style="text-align:right;">Cash returned (Rs)</th>
                </tr></thead>
                <tbody>
                    @foreach($ownerTransferReturns as $return)
                        <tr style="background:{{ $return->transfer->deleted_at ? '#fff1f2' : '#ecfdf5' }};color:{{ $return->transfer->deleted_at ? '#b91c1c' : '#065f46' }};">
                            <td class="px-4 py-3">{{ $return->returned_at->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">OTR-{{ $return->id }} / OT-{{ $return->owner_transfer_id }}</td>
                            <td class="px-4 py-3">{{ $return->description }}
                                @if($return->transfer->deleted_at)<br><strong>Deleted with transfer</strong> — {{ $return->transfer->deletion_reason }} (original return Rs {{ number_format($return->amount, 2) }})@endif
                            </td>
                            <td class="px-4 py-3 font-mono" style="text-align:right;font-weight:bold;">{{ number_format($return->effective_amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        <table class="min-w-full text-sm" style="width:100%;margin-top:12px;">
            <tbody>
                <tr style="background:#eef2ff;color:#3730a3;font-weight:bold;"><td class="px-4 py-3">Gross transfers to owner account</td><td class="px-4 py-3" style="text-align:right;">Rs {{ number_format($grossTransfers ?? (float) $ownerTransfers->sum('effective_amount'), 2) }}</td></tr>
                <tr style="background:#ecfdf5;color:#065f46;font-weight:bold;"><td class="px-4 py-3">Returned to cash</td><td class="px-4 py-3" style="text-align:right;">Rs {{ number_format($totalTransferReturns ?? (float) $ownerTransferReturns->sum('effective_amount'), 2) }}</td></tr>
                <tr style="background:#e0e7ff;color:#312e81;font-weight:bold;"><td class="px-4 py-3">Net transfers in selected period</td><td class="px-4 py-3" style="text-align:right;">Rs {{ number_format($totalTransfers, 2) }}</td></tr>
            </tbody>
        </table>
    </div>
@endif
