<form method="POST" action="{{ route('class-fees.store', ['classroom' => $class->id, 'type' => $selectedCycle['schedule']->fee_type]) }}" class="space-y-4" x-data="{ method: @js(old('payment_method', 'cash')), saving: false }" @submit="saving = true">
    @csrf
    @if($collectionMode ?? false)
        <input type="hidden" name="return_to" value="collection">
        <input type="hidden" name="search_class_room_id" value="{{ $filters['class_room_id'] ?? '' }}">
        <input type="hidden" name="search_q" value="{{ $filters['q'] ?? '' }}">
        <input type="hidden" name="search_year" value="{{ $year }}">
        <input type="hidden" name="all_years" value="{{ (int) ($filters['all_years'] ?? true) }}">
    @endif
    <input type="hidden" name="include_inactive" value="{{ (int) ($filters['include_inactive'] ?? false) }}">
    <input type="hidden" name="include_former" value="{{ (int) ($filters['include_former'] ?? false) }}">
    <input type="hidden" name="student_id" value="{{ $selectedStudent->id }}">
    <input type="hidden" name="cycle" value="{{ $selectedCycle['key'] }}">
    <input type="hidden" name="submission_id" value="{{ old('submission_id', $submissionId) }}">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div><x-input-label for="amount" value="Amount to collect (Rs)" /><x-text-input id="amount" name="amount" type="number" min="0.01" step="0.01" :max="$selectedStatus['collectable']" :value="old('amount', $selectedStatus['collectable'])" required class="mt-1 w-full" /></div>
        <div><x-input-label for="paid_at" value="Payment date" /><x-text-input id="paid_at" name="paid_at" type="date" :max="today()->toDateString()" :value="old('paid_at', today()->toDateString())" required class="mt-1 w-full" /></div>
        <div><x-input-label for="payment_method" value="Payment method" /><select name="payment_method" id="payment_method" x-model="method" class="mt-1 w-full rounded-md border-gray-300"><option value="cash">Cash</option><option value="bank_transfer">Bank transfer</option><option value="cheque">Cheque (pending until passed)</option></select></div>
    </div>
    <div x-show="method === 'bank_transfer'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div><x-input-label for="bank_name" value="Bank name" /><x-text-input id="bank_name" name="bank_name" :value="old('bank_name')" class="mt-1 w-full" /></div>
        <div><x-input-label for="bank_ref_no" value="Bank reference (optional)" /><x-text-input id="bank_ref_no" name="bank_ref_no" :value="old('bank_ref_no')" class="mt-1 w-full" /></div>
    </div>
    <div x-show="method === 'cheque'" x-cloak class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div><x-input-label for="cheque_bank" value="Cheque bank" /><x-text-input id="cheque_bank" name="cheque_bank" :value="old('cheque_bank')" class="mt-1 w-full" /></div>
        <div><x-input-label for="cheque_number" value="Cheque number" /><x-text-input id="cheque_number" name="cheque_number" :value="old('cheque_number')" class="mt-1 w-full" /></div>
        <div><x-input-label for="cheque_date" value="Cheque date" /><x-text-input id="cheque_date" name="cheque_date" type="date" :value="old('cheque_date')" class="mt-1 w-full" /></div>
    </div>
    <div class="rounded-lg bg-indigo-50 p-4"><input type="hidden" name="record_revenue" value="0"><label class="flex items-center gap-2 text-sm font-semibold text-indigo-900"><input type="checkbox" name="record_revenue" value="1" @checked(old('record_revenue', true)) class="rounded border-gray-300 text-indigo-600"> Include in revenue &amp; ledger</label><p class="mt-1 text-xs text-indigo-800">Uncheck when entering a payment already made in an earlier term, to settle the fee without adding income again.</p></div>
    <div><x-input-label for="notes" value="Notes (optional)" /><x-text-input id="notes" name="notes" maxlength="1000" :value="old('notes')" class="mt-1 w-full" /></div>
    <x-primary-button x-bind:disabled="saving" x-text="saving ? 'Saving…' : 'Record payment'">Record payment</x-primary-button>
</form>
