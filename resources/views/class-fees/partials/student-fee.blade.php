@php
    $key = $cycle['key'];
    $installment = $row['installments'][$key];
    $feeType = $cycle['schedule']->fee_type;
    $label = $feeType === 'facility' ? $cycle['due']->format('Y').' fee' : ($feeType === 'computer' ? 'Computer Fee' : 'Exam Fee');
    $state = $installment['pending'] > 0 ? 'pending' : ($installment['remaining'] < 0.01 && $installment['eligible'] ? 'paid' : ($installment['due'] > 0 ? 'due' : 'muted'));
    $hasActivity = $installment['eligible'] || $installment['paid'] > 0 || $installment['pending'] > 0;
    $context = ['class_room_id' => $section['class']?->id, 'type' => $type, 'year' => $cycle['due']->year, 'student_id' => $row['student']->id, 'cycle' => $key, 'include_inactive' => 1, 'include_former' => 1, 'include_facility' => 1];
    $activeReceipts = $installment['receipts']->reject(fn ($receipt) => $receipt->isCancelled());
@endphp
<div class="fee-register-fee" data-fee-type="{{ $feeType }}" data-fee-cycle="{{ $key }}">
    <div class="fee-register-fee-heading"><span>{{ $label }}</span><span class="fee-register-badge fee-register-badge-{{ $state }}">{{ $installment['status'] }}</span></div>
    @if($hasActivity)
        <div class="fee-register-fee-amounts">
            <span><strong>{{ $installment['remaining'] > 0 ? 'Balance' : 'Paid' }} Rs {{ number_format($installment['remaining'] > 0 ? $installment['remaining'] : $installment['paid'], 2) }}</strong></span>
            <div class="fee-register-inline-actions">
                @can('revenue.add')
                    @if($installment['eligible'] && $installment['collectable'] > 0)
                        <a class="fee-register-pay-link" data-register-action="pay" aria-label="Pay {{ $label }} for {{ $row['student']->name }} · {{ $cycle['due']->format('d M Y') }}" href="{{ route('class-fees.index', $context) }}#collect-fee">{{ $cycle['due']->gt(today()) ? 'Pay ahead' : 'Pay' }}</a>
                    @endif
                @endcan
                @can('revenue.delete')
                    @if($installment['paid'] > 0)<a class="fee-register-correction-link" data-register-action="unpaid" aria-label="Make {{ $label }} unpaid for {{ $row['student']->name }} · {{ $cycle['due']->format('d M Y') }}" href="{{ route('class-fees.index', $context) }}">Make unpaid</a>@endif
                @endcan
            </div>
        </div>
        @if($installment['remaining'] > 0 && $installment['paid'] > 0)<div class="fee-register-sub">Paid Rs {{ number_format($installment['paid'], 2) }}</div>@endif
        @if($installment['pending'] > 0)<div class="fee-register-sub">Pending Rs {{ number_format($installment['pending'], 2) }} · awaiting clearance</div>@endif
        @if($installment['waived'] > 0)<div class="fee-register-sub">Waived Rs {{ number_format($installment['waived'], 2) }}</div>@endif
        @can('revenue.manage')
            @if($activeReceipts->isNotEmpty())
                <details class="fee-register-payment-details">
                    <summary>{{ $activeReceipts->count() === 1 ? 'Payment details / edit' : $activeReceipts->count().' payments / edit' }}</summary>
                    @foreach($activeReceipts as $receipt)
                        @php($source = $receipt instanceof \App\Models\Revenue ? 'revenue' : 'history')
                        <div class="fee-register-receipt-link">{{ $source === 'revenue' ? ($receipt->bill_no ?: 'Receipt #'.$receipt->id) : 'History #'.$receipt->id }} <a href="{{ route('class-fees.payments.edit', ['source' => $source, 'payment' => $receipt->id]) }}">Edit payment</a></div>
                    @endforeach
                </details>
            @endif
        @endcan
    @endif
</div>
