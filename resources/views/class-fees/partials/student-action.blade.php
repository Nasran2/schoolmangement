@php($feeLabel = \App\Models\ClassFeeSchedule::TYPES[$selectedCycle['schedule']->fee_type].(isset($selectedCycle['term']) ? ' · Term '.$selectedCycle['term'] : ''))
<div class="fee-register-context"><strong>{{ $selectedStudent->name }}</strong> · {{ $selectedStudent->admission_number }}<br>{{ $class->name }} · {{ $feeLabel }}<br>{{ $selectedCycle['due']->format('d M Y') }}@if(isset($selectedCycle['end'])) – {{ $selectedCycle['end']->format('d M Y') }}@endif<br>Paid Rs {{ number_format($selectedStatus['paid'], 2) }} · Remaining Rs {{ number_format($selectedStatus['remaining'], 2) }}@if($selectedStatus['pending'] > 0) · Pending cheque Rs {{ number_format($selectedStatus['pending'], 2) }}@endif</div>
@if($action === 'pay')
    @if($selectedStatus['eligible'] && $selectedStatus['collectable'] > 0)
        <form method="POST" data-register-payment action="{{ route('class-fees.store', ['classroom' => $class->id, 'type' => $selectedCycle['schedule']->fee_type]) }}">
            @csrf
            <input type="hidden" name="return_to" value="students"><input type="hidden" name="student_id" value="{{ $selectedStudent->id }}"><input type="hidden" name="cycle" value="{{ $selectedCycle['key'] }}"><input type="hidden" name="submission_id" value="{{ $submissionId }}"><input type="hidden" name="record_revenue" value="1" data-register-revenue-value>
            <div class="fee-register-fields">
                <div><label for="register-amount">Amount to pay (Rs)</label><input id="register-amount" name="amount" type="number" min="0.01" max="{{ $selectedStatus['collectable'] }}" step="0.01" value="{{ $selectedStatus['collectable'] }}" required></div>
                <div><label for="register-paid-at">Payment date</label><input id="register-paid-at" name="paid_at" type="date" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required></div>
                <div><label for="register-method">Payment method</label><select id="register-method" name="payment_method" data-register-method><option value="cash">Cash</option><option value="bank_transfer">Bank transfer</option><option value="cheque">Cheque (pending until passed)</option></select></div>
            </div>
            <div class="fee-register-fields" data-register-method-fields="bank_transfer" hidden>
                <div><label for="register-bank">Bank name</label><input id="register-bank" name="bank_name" maxlength="120" data-method-required disabled></div>
                <div><label for="register-bank-ref">Bank reference (optional)</label><input id="register-bank-ref" name="bank_ref_no" maxlength="120" disabled></div>
            </div>
            <div class="fee-register-fields" data-register-method-fields="cheque" hidden>
                <div><label for="register-cheque-bank">Cheque bank</label><input id="register-cheque-bank" name="cheque_bank" maxlength="100" data-method-required disabled></div>
                <div><label for="register-cheque-number">Cheque number</label><input id="register-cheque-number" name="cheque_number" maxlength="100" data-method-required disabled></div>
                <div><label for="register-cheque-date">Cheque date</label><input id="register-cheque-date" name="cheque_date" type="date" data-method-required disabled></div>
            </div>
            <div class="mt-4 rounded-lg bg-indigo-50 p-4"><label style="display:flex;align-items:center;gap:8px;"><input type="checkbox" data-register-revenue checked> Include in revenue &amp; ledger</label><p class="fee-register-sub">Uncheck for a historical payment. Cheques must stay in revenue until passed or returned.</p></div>
            <div class="mt-4"><label for="register-notes">Notes (optional)</label><textarea id="register-notes" name="notes" maxlength="1000" rows="2"></textarea></div>
            <div data-register-form-errors hidden class="fee-register-error" role="alert"></div>
            <div class="mt-5 flex flex-wrap gap-3"><button type="submit" class="fee-register-submit">Save payment</button><button type="button" class="fee-register-button" data-register-close>Cancel</button></div>
        </form>
    @else
        <p class="mt-4 text-sm text-gray-600">{{ !$selectedStatus['eligible'] ? 'This fee does not apply to the student.' : ($selectedStatus['remaining'] < 0.01 ? 'This fee is already settled.' : 'The remaining amount is reserved by a pending cheque.') }}</p>
    @endif
@else
    <ul class="fee-register-receipts">@foreach($preview['payments'] as $receipt)<li><strong>{{ $receipt['reference'] }}</strong> · {{ $receipt['date'] }} · Rs {{ number_format($receipt['amount'], 2) }}<br>{{ $receipt['accounting'] }}</li>@endforeach</ul>
    @if($preview['blocked_reason'])<div class="fee-register-error">{{ $preview['blocked_reason'] }}</div>
    @elseif($selectedStatus['paid'] <= 0)<p class="mt-4 text-sm text-gray-600">There is no paid amount to reverse.</p>
    @else
        <form method="POST" data-register-payment action="{{ route('class-fees.bulk.unpaid') }}">
            @csrf
            <input type="hidden" name="return_to" value="students"><input type="hidden" name="year" value="{{ $selectedCycle['due']->year }}"><input type="hidden" name="submission_id" value="{{ $submissionId }}">
            <input type="hidden" name="lines_payload" value="{{ json_encode([['student_id' => $selectedStudent->id, 'class_room_id' => $class->id, 'cycle' => $selectedCycle['key'], 'fingerprint' => $preview['fingerprint']]]) }}">
            <div class="fee-register-context">The listed payments will be cancelled and the balance reopened. Revenue receipts are removed from income; historical payments change only the fee balance. Pending cheques on this fee are also cancelled. Bills and audit history are retained. This does not record a cash refund.</div>
            <div class="mt-4"><label for="register-unpaid-reason">Reason for making this fee unpaid</label><textarea id="register-unpaid-reason" name="reason" rows="3" minlength="3" maxlength="1000" required></textarea></div>
            <div data-register-form-errors hidden class="fee-register-error" role="alert"></div>
            <div class="mt-5 flex flex-wrap gap-3"><button type="submit" class="fee-register-submit fee-register-submit-danger">Make unpaid</button><button type="button" class="fee-register-button" data-register-close>Cancel</button></div>
        </form>
    @endif
@endif
