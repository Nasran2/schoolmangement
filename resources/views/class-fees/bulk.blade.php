<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-xl font-semibold text-gray-900">Bulk fee payments</h2><p class="mt-1 text-sm text-gray-600">Select a class or find students, add their terms to the list, then review and save together.</p></div>
            <a href="{{ route('class-fees.index', ['year' => $year, 'class_room_id' => $filters['class_room_id'] ?? null]) }}" class="text-sm font-semibold text-indigo-700 underline">Back to payment status</a>
        </div>
    </x-slot>
    <div class="max-w-7xl mx-auto py-8 space-y-5" x-data="bulkClassFees(@js($candidates), @js($filters['class_room_id'] ?? ''), @js(old('lines', [])))">
        @if(session('status'))<div role="status" class="rounded-lg bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if($errors->any())<div role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <form method="GET" class="rounded-lg border border-gray-200 bg-white p-5 flex flex-wrap items-end gap-4">
            <div><label for="bulk-year" class="block text-sm font-medium text-gray-700">Installment year</label><input id="bulk-year" type="number" name="year" value="{{ $year }}" min="2000" max="2099" required class="mt-1 rounded-md border-gray-300 w-32"></div>
            <label class="flex items-center gap-2 text-sm text-gray-600 mb-2"><input type="checkbox" name="include_inactive" value="1" @checked($filters['include_inactive'] ?? false) class="rounded border-gray-300"> Include inactive / alumni for historical payments</label>
            <x-secondary-button type="submit" x-bind:disabled="lines.length > 0">Load year</x-secondary-button>
            <p class="text-xs text-gray-500">Save or clear your payment list before changing the year.</p>
        </form>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <section class="lg:col-span-1 rounded-lg bg-white border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-5 space-y-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">1. Choose students &amp; terms</h3>
                    <div><label for="bulk-class" class="block text-sm font-medium text-gray-700">Class</label><select id="bulk-class" x-model="classId" @change="selected = []" class="mt-1 w-full rounded-md border-gray-300"><option value="">All classes</option>@foreach($classes as $option)<option value="{{ $option->id }}">{{ $option->name }}</option>@endforeach</select></div>
                    <div><label for="bulk-search" class="block text-sm font-medium text-gray-700">Search students</label><input id="bulk-search" type="search" x-model.debounce.200ms="query" placeholder="Name or admission number" class="mt-1 w-full rounded-md border-gray-300"></div>
                    <div><label for="bulk-term" class="block text-sm font-medium text-gray-700">Installments to add</label><select id="bulk-term" x-model="installment" @change="selected = []" class="mt-1 w-full rounded-md border-gray-300"><option value="exam">All exam terms</option><option value="term:1">Term 1 only</option><option value="term:2">Term 2 only</option><option value="term:3">Term 3 only</option><option value="facility">Facility fee only</option><option value="all">Exam terms &amp; facility fee</option></select></div>
                    <p class="text-xs text-gray-500">Only applicable installments with a remaining balance are listed. Future terms can be paid in advance.</p>
                    <label class="flex items-center gap-2 text-sm font-semibold text-indigo-700"><input type="checkbox" :checked="allSelected" @change="toggleAll($event.target.checked)" class="rounded border-gray-300 text-indigo-600">Select all matching students <span x-text="'(' + visible.length + ')'"></span></label>
                </div>
                <div class="max-h-96 overflow-y-auto divide-y divide-gray-100">
                    <template x-for="student in visible" :key="student.key"><label class="flex gap-3 p-4 hover:bg-indigo-50 cursor-pointer"><input type="checkbox" x-model="selected" :value="student.key" class="mt-1 rounded border-gray-300 text-indigo-600"><span><span class="block text-sm font-semibold text-gray-900" x-text="student.name"></span><span class="block text-xs text-gray-500" x-text="student.admission + ' · ' + student.class_name"></span></span></label></template>
                    <p x-show="visible.length === 0" class="p-5 text-sm text-gray-500">No students with an available installment match. Check the year, class, or term calendar.</p>
                </div>
                <div class="p-4 border-t border-gray-100"><button type="button" @click="addSelected()" :disabled="selected.length === 0" class="w-full rounded-md bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">Add selected to payment list <span x-text="selected.length ? '(' + selected.length + ')' : ''"></span></button></div>
            </section>
            <form method="POST" action="{{ route('class-fees.bulk.store') }}" class="lg:col-span-2 space-y-5" @submit="saving = true">
                @csrf
                <input type="hidden" name="lines_payload" :value="JSON.stringify(lines.map(line => ({ student_id: line.student_id, class_room_id: line.class_room_id, cycle: line.cycle, amount: line.amount, record_revenue: line.record_revenue ? 1 : 0 })))">
                <input type="hidden" name="submission_id" value="{{ old('submission_id', $submissionId) }}"><input type="hidden" name="year" value="{{ $year }}">
                <section class="rounded-lg bg-white border border-gray-200 shadow-sm overflow-hidden">
                    <div class="p-5 flex flex-wrap justify-between gap-3 border-b border-gray-100"><h3 class="font-semibold text-gray-900">2. Review payment list <span class="text-indigo-700" x-text="'(' + lines.length + ')'"></span></h3><button type="button" @click="lines = []" :disabled="saving" class="text-sm text-gray-500 underline">Clear list</button></div>
                    <div role="alert" x-show="message" x-cloak x-text="message" class="m-4 p-3 rounded bg-amber-50 text-sm text-amber-800"></div>
                    <div class="overflow-x-auto max-h-96">
                        <table class="w-full text-sm"><thead class="bg-gray-50 text-gray-600 sticky top-0"><tr><th class="text-left p-3">Student / installment</th><th class="text-left p-3">Amount (Rs)</th><th class="p-3 text-center">Revenue<br>&amp; ledger</th><th class="p-3"><span class="sr-only">Remove</span></th></tr></thead>
                            <tbody class="divide-y divide-gray-100"><template x-for="(line, index) in lines" :key="line.key"><tr>
                                <td class="p-3 min-w-48"><div class="font-semibold" x-text="line.name"></div><div class="text-xs text-gray-500" x-text="line.admission + ' · ' + line.class_name"></div><div class="text-xs text-indigo-700 mt-1" x-text="line.label"></div>

                                </td>
                                <td class="p-3"><input type="number" :aria-label="'Amount for ' + line.name + ', ' + line.label" x-model="line.amount" min="0.01" :max="line.balance" step="0.01" required class="w-32 rounded-md border-gray-300 text-right"><div class="text-xs text-gray-500 mt-1" x-text="'Balance ' + money(line.balance)"></div></td>
                                <td class="p-3 text-center"><input type="checkbox" :aria-label="'Include ' + line.name + ', ' + line.label + ' in revenue and ledger'" x-model="line.record_revenue" class="rounded border-gray-300 text-indigo-600"></td>
                                <td class="p-3"><button type="button" @click="lines.splice(index, 1)" :disabled="saving" :aria-label="'Remove ' + line.name + ', ' + line.label" class="text-red-700 hover:underline">Remove</button></td>
                            </tr></template></tbody>
                        </table>
                        <div x-show="lines.length === 0" class="p-10 text-center"><p class="font-semibold text-gray-800">Your payment list is empty</p><p class="mt-2 text-sm text-gray-500">Choose students and terms, then add them here. Search again to add students from another class.</p></div>
                    </div>
                    <div class="p-5 bg-indigo-50 border-t border-indigo-100 space-y-3">
                        <label class="flex items-center gap-2 text-sm font-semibold text-indigo-900"><input type="checkbox" x-model="recordRevenue" @change="applyRevenue()" class="rounded border-gray-300 text-indigo-600"> Include all payments in revenue &amp; ledger</label>
                        <p class="text-xs text-indigo-800">Checked by default. For fees already paid in earlier terms, uncheck to update student payment history without receiving that income again. You can also change each row separately.</p>
                        <div class="flex flex-wrap gap-5 text-sm"><span>Total payments <strong x-text="'Rs ' + money(total)"></strong></span><span>Added to revenue <strong x-text="'Rs ' + money(income)"></strong></span></div>
                    </div>
                </section>
                <section class="rounded-lg bg-white border border-gray-200 p-5 space-y-4" x-data="{ method: @js(old('payment_method', 'cash')) }">
                    <h3 class="font-semibold text-gray-900">3. Payment details</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><label for="bulk-paid-at" class="block text-sm font-medium text-gray-700">Payment date</label><input id="bulk-paid-at" name="paid_at" type="date" value="{{ old('paid_at', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required class="mt-1 w-full rounded-md border-gray-300"><p class="mt-1 text-xs text-gray-500">Use the original date for historical payments. Save separate batches for different payment dates.</p></div>
                        <div><label for="bulk-method" class="block text-sm font-medium text-gray-700">Payment method</label><select id="bulk-method" name="payment_method" x-model="method" class="mt-1 w-full rounded-md border-gray-300"><option value="cash">Cash</option><option value="bank_transfer">Bank transfer</option></select></div>
                    </div>
                    <div x-show="method === 'bank_transfer'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><label for="bulk-bank" class="block text-sm font-medium text-gray-700">Bank name</label><input id="bulk-bank" name="bank_name" value="{{ old('bank_name') }}" :required="method === 'bank_transfer'" class="mt-1 w-full rounded-md border-gray-300" maxlength="120"></div>
                        <div><label for="bulk-bank-ref" class="block text-sm font-medium text-gray-700">Bank reference (optional)</label><input id="bulk-bank-ref" name="bank_ref_no" value="{{ old('bank_ref_no') }}" class="mt-1 w-full rounded-md border-gray-300" maxlength="120"></div>
                    </div>
                    <div><label for="bulk-notes" class="block text-sm font-medium text-gray-700">Notes (optional)</label><input id="bulk-notes" name="notes" value="{{ old('notes') }}" maxlength="1000" class="mt-1 w-full rounded-md border-gray-300"></div>
                    <button type="submit" :disabled="lines.length === 0 || saving" class="rounded-md bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50" x-text="saving ? 'Saving payments…' : 'Save ' + lines.length + ' payments'">Save payments</button>
                    <p class="text-xs text-gray-500">All rows are checked together. If any balance has changed, the entire batch stays unsaved so you can correct it.</p>
                </section>
            </form>
        </div>
    </div>
</x-app-layout>
