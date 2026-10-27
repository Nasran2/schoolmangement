@php
    $primary = $bundleItems->first();
    $active = $bundleItems->reject(fn ($line) => $line->isCancelled() || $line->payment_status === 'rejected');
    $refunded = (float) $bundleItems->sum(fn ($line) => $line->adjustments->where('type', 'refund')->sum('amount'));
    $receiptSummary = [
        'amount' => round((float) $active->sum('amount'), 2),
        'refunded' => $refunded,
        'waived' => (float) $bundleItems->sum(fn ($line) => $line->adjustments->where('type', 'waiver')->sum('amount')),
        'net_collected' => max(0, round((float) $active->whereNotIn('payment_status', ['hold', 'pending'])->sum('amount') - $refunded, 2)),
        'pending' => $active->whereIn('payment_status', ['hold', 'pending'])->isNotEmpty(),
        'invalid' => $active->isEmpty(),
        'returned' => $bundleItems->every(fn ($line) => $line->payment_status === 'rejected'),
        'has_invalid' => $active->count() !== $bundleItems->count(),
        'bill_no' => $primary->payment_meta['fee_bundle']['bill_no'] ?? $primary->bill_no,
    ];
@endphp
{{-- Use the established school receipt and print layout for combined payments too. --}}
@include('revenue.receipt', ['revenue' => $primary, 'receiptItems' => $bundleItems, 'receiptSummary' => $receiptSummary])
