<form method="POST" action="{{ route('class-fees.store', ['classroom' => $class->id, 'type' => $type]) }}" @submit="saving = true">
    @csrf
    <input type="hidden" name="return_to" value="collection">
    <input type="hidden" name="search_class_room_id" value="{{ $filters['class_room_id'] ?? '' }}">
    <input type="hidden" name="search_q" :value="query" value="{{ $filters['q'] ?? '' }}">
    <input type="hidden" name="search_year" value="{{ $year }}">
    <input type="hidden" name="all_years" value="{{ (int) ($filters['all_years'] ?? true) }}">
    <input type="hidden" name="include_inactive" value="{{ (int) ($filters['include_inactive'] ?? false) }}">
    <input type="hidden" name="student_id" value="{{ $selectedStudent->id }}">
    <input type="hidden" name="cycle" value="{{ $selectedCycle['key'] }}">
    <input type="hidden" name="submission_id" value="{{ old('submission_id', $submissionId) }}">
    <input type="hidden" name="view_receipt" :value="recordRevenue && viewReceipt ? 1 : 0" value="1">
    <div class="fee-two-fields">
        <div class="fee-field"><label for="amount">Amount (Rs)</label><input id="amount" class="fee-input" type="number" name="amount" min="0.01" step="0.01" max="{{ $selectedStatus['collectable'] }}" x-model="amount" value="{{ old('amount', $selectedStatus['collectable']) }}" required><p class="fee-hint">Up to Rs {{ number_format($selectedStatus['collectable'], 2) }} for this installment. Partial payments are allowed.</p>@if($type === 'facility')<p class="fee-hint">Configured facility fee: Rs {{ number_format($selectedCycle['amount'], 2) }}. The unpaid balance is filled in by default; you can enter a smaller amount.</p><button type="button" class="fee-link" @click="amount = defaultAmount">Use remaining facility fee</button>@endif</div>
        <div class="fee-field"><label for="paid_at">Payment Date</label><input id="paid_at" class="fee-input" type="date" name="paid_at" x-model="date" max="{{ today()->toDateString() }}" value="{{ old('paid_at', today()->toDateString()) }}" required></div>
    </div>
    <fieldset class="fee-field"><legend class="fee-field-label">Payment Method</legend><div class="fee-methods">
        @foreach(['cash' => ['Cash', 'No extra details'], 'bank_transfer' => ['Bank Transfer', 'Bank + reference'], 'cheque' => ['Cheque', 'Pending confirmation']] as $methodValue => $methodLabels)
            <label class="fee-method" :class="method === '{{ $methodValue }}' ? 'is-selected' : ''"><input type="radio" name="payment_method" value="{{ $methodValue }}" x-model="method" @change="methodChanged()" @checked(old('payment_method', 'cash') === $methodValue)><span><strong>{{ $methodLabels[0] }}</strong><small>{{ $methodLabels[1] }}</small></span></label>
        @endforeach
    </div>
    <div x-show="method === 'bank_transfer'" x-cloak class="fee-two-fields fee-method-details">
        <div><label class="fee-field-label" for="bank_name">Bank name</label><input id="bank_name" class="fee-input" name="bank_name" value="{{ old('bank_name') }}" :required="method === 'bank_transfer'" maxlength="120"></div>
        <div><label class="fee-field-label" for="bank_ref_no">Reference (optional)</label><input id="bank_ref_no" class="fee-input" name="bank_ref_no" value="{{ old('bank_ref_no') }}" maxlength="120"></div>
    </div>
    <div x-show="method === 'cheque'" x-cloak class="fee-method-details">
        <div class="fee-income"><p class="fee-hint">The cheque stays pending until marked passed. Its amount reserves this installment, but does not settle dues or count as collected income until clearance. A returned cheque reopens collection.</p></div>
        <div class="fee-two-fields">
            <div class="fee-field"><label for="cheque_date">Cheque date</label><input id="cheque_date" class="fee-input" type="date" name="cheque_date" value="{{ old('cheque_date') }}" :required="method === 'cheque'"></div>
            <div class="fee-field"><label for="cheque_number">Cheque number</label><input id="cheque_number" class="fee-input" name="cheque_number" value="{{ old('cheque_number') }}" maxlength="100" :required="method === 'cheque'"></div>
            <div><label class="fee-field-label" for="cheque_bank">Cheque bank</label><input id="cheque_bank" class="fee-input" name="cheque_bank" value="{{ old('cheque_bank') }}" maxlength="100" :required="method === 'cheque'"></div>
            <div><label class="fee-field-label" for="cheque_student_name">Name on cheque (optional)</label><input id="cheque_student_name" class="fee-input" name="cheque_student_name" value="{{ old('cheque_student_name', $selectedStudent->name) }}" maxlength="120"></div>
        </div>
    </div></fieldset>
    <input type="hidden" name="record_revenue" value="1">
    <div class="fee-income"><p class="fee-hint">This payment is recorded in revenue and the ledger. Cheques count as income only after they pass.</p></div>
    <div class="fee-field" x-show="recordRevenue"><label for="bill_no">Bill Number {{ $autogenerate ? '(automatic)' : '(optional)' }}</label><input id="bill_no" class="fee-input" name="bill_no" maxlength="50" value="{{ old('bill_no', $nextBillNumberPreview) }}" @readonly($autogenerate)><p class="fee-hint">{{ $autogenerate ? 'Next bill number preview. The final number is assigned when you save.' : 'Enter a unique bill number or keep the suggested number.' }}</p></div>
    <div class="fee-field"><label for="notes">Notes (optional)</label><textarea id="notes" class="fee-input" name="notes" rows="3" maxlength="1000" placeholder="Add any additional notes…">{{ old('notes') }}</textarea></div>
    <div class="fee-actions">
        <button type="submit" class="fee-button fee-button-wide" @click="viewReceipt = true" :disabled="saving" x-show="recordRevenue"><span x-text="saving ? 'Saving…' : 'Pay {{ $feeLabel }} & view bill'">Pay {{ $feeLabel }} &amp; view bill</span></button>
        <button type="submit" class="fee-button fee-button-secondary" @click="viewReceipt = false" :disabled="saving"><span x-text="recordRevenue ? 'Pay {{ $feeLabel }}' : 'Save historical payment'">Pay {{ $feeLabel }}</span></button>
        <a class="fee-button fee-button-secondary" href="{{ route('revenue.items.create', ['quick' => $type]) }}">Cancel</a>
    </div>
</form>
