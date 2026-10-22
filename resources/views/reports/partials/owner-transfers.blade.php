@if($ownerTransfers->isNotEmpty())
    <div class="mt-6" style="margin-top: 20px;">
        <h3 class="text-lg font-bold text-gray-900 mb-3">Transfers to Owner bank account</h3>
        <p class="text-sm text-gray-600 mb-3">Hand cash transferred to the owner. Excluded from expenses and profit.</p>
        <table class="min-w-full text-sm" style="width: 100%;">
            <thead><tr>
                <th class="px-4 py-3 text-left" style="text-align: left;">Date</th>
                <th class="px-4 py-3 text-left" style="text-align: left;">Reference</th>
                <th class="px-4 py-3 text-left" style="text-align: left;">Notes</th>
                <th class="px-4 py-3 text-right" style="text-align: right;">Amount (Rs)</th>
            </tr></thead>
            <tbody>
                @foreach($ownerTransfers as $transfer)
                    <tr>
                        <td class="px-4 py-3">{{ $transfer->transferred_at->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">{{ $transfer->reference ?: 'OT-'.$transfer->id }}</td>
                        <td class="px-4 py-3">{{ $transfer->notes ?: '—' }}</td>
                        <td class="px-4 py-3 text-right font-mono" style="text-align: right;">{{ number_format($transfer->amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="font-semibold">
                    <td class="px-4 py-3" colspan="3">Total transfers</td>
                    <td class="px-4 py-3 text-right font-mono" style="text-align: right;">{{ number_format($totalTransfers, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
@endif
