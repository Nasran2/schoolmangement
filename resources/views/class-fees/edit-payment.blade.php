<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-900">Edit fee payment</h2></x-slot>
    @php($schedule = $item->classFeeSchedule)
    <div class="max-w-3xl mx-auto py-8 space-y-5">
        @if(session('status'))<div role="status" class="rounded-lg bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if($errors->any())<div role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <div class="rounded-lg bg-white p-6 shadow-sm space-y-5">
            <div><h3 class="text-lg font-semibold">{{ $item->student->name }}</h3><p class="mt-1 text-sm text-gray-500">{{ $item->student->admission_number }} · {{ $schedule->classRoom->name }} · {{ \App\Models\ClassFeeSchedule::TYPES[$schedule->fee_type] }} · Installment {{ $item->class_fee_due_date->format('d M Y') }}</p><p class="mt-1 text-xs text-gray-500">{{ $source === 'revenue' ? 'Revenue receipt '.$item->bill_no : 'Historical payment #'.$item->id.' · excluded from revenue and ledger' }}</p></div>
            <form method="POST" action="{{ route('class-fees.payments.update', ['source' => $source, 'payment' => $item->id]) }}" class="space-y-5" x-data="{ revenue: @js((bool) old('record_revenue', $source === 'revenue')), method: @js(old('payment_method', 'cash')), saving: false }" @submit="saving = true">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><x-input-label for="amount" value="Paid amount (Rs)" /><x-text-input id="amount" name="amount" type="number" min="0.01" max="9999999999.99" step="0.01" :value="old('amount', $item->amount)" class="mt-1 w-full" required /></div>
                    <div><x-input-label for="paid_at" value="Payment date" /><x-text-input id="paid_at" name="paid_at" type="date" :max="today()->toDateString()" :value="old('paid_at', $item->paid_at->toDateString())" class="mt-1 w-full" required /></div>
                </div>
                <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4"><input type="hidden" name="record_revenue" :value="revenue ? 1 : 0"><label class="flex items-center gap-2 text-sm font-semibold text-indigo-900"><input type="checkbox" x-model="revenue" class="rounded border-gray-300 text-indigo-600"> Include in revenue &amp; ledger</label><p class="mt-2 text-sm text-indigo-800">Uncheck for a historical payment that should only settle the student’s fee. Changing this option updates revenue and ledger totals while preserving an audit record.</p></div>
                @if($source === 'history')
                    <div x-show="revenue" x-cloak class="space-y-4"><div><x-input-label for="payment_method" value="Method for the revenue entry" /><select id="payment_method" name="payment_method" x-model="method" class="mt-1 rounded-md border-gray-300"><option value="cash">Cash</option><option value="bank_transfer">Bank transfer</option></select></div><div x-show="method === 'bank_transfer'" class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><x-input-label for="bank_name" value="Bank name" /><x-text-input id="bank_name" name="bank_name" :value="old('bank_name')" class="mt-1 w-full" /></div><div><x-input-label for="bank_ref_no" value="Bank reference" /><x-text-input id="bank_ref_no" name="bank_ref_no" :value="old('bank_ref_no')" class="mt-1 w-full" /></div></div></div>
                @endif
                <div><x-input-label for="notes" value="Notes (optional)" /><textarea id="notes" name="notes" maxlength="1000" rows="3" class="mt-1 w-full rounded-md border-gray-300">{{ old('notes', $item->notes) }}</textarea></div>
                <div class="flex items-center gap-4"><x-primary-button x-bind:disabled="saving">Save changes</x-primary-button><a class="text-sm text-indigo-700 underline" href="{{ route('class-fees.index', ['class_room_id' => $schedule->class_room_id, 'type' => $schedule->fee_type, 'year' => $item->class_fee_due_date->year]) }}">Back to student payments</a></div>
            </form>
        </div>
        @can('revenue.delete')
            <details class="rounded-lg border border-red-200 bg-white p-5"><summary class="text-sm font-semibold text-red-700 cursor-pointer">Delete incorrect payment</summary><p class="mt-3 text-sm text-gray-600">The payment will be cancelled, its fee balance reopened, and any income removed from revenue and the ledger. An audit record is kept.</p><form method="POST" action="{{ route('class-fees.payments.destroy', ['source' => $source, 'payment' => $item->id]) }}" class="mt-4 space-y-3" x-data="{ deleting: false }" @submit="deleting = true">@csrf @method('DELETE')<label for="delete-reason" class="block text-sm text-gray-700">Reason</label><input id="delete-reason" name="reason" minlength="3" maxlength="1000" required class="w-full rounded-md border-gray-300" placeholder="Why was this payment incorrect?"><button type="submit" :disabled="deleting" class="rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Delete payment</button></form></details>
        @endcan
    </div>
</x-app-layout>
