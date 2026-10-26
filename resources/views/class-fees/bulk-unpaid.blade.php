<x-app-layout>
    @include('class-fees.partials.scripts')
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-xl font-semibold text-gray-900">Bulk make fees unpaid</h2><p class="mt-1 text-sm text-gray-600">Select paid fees, review their receipts, and correct mistaken payments together.</p></div>
            @canany(['class_fees.view', 'revenue.add', 'revenue.manage', 'revenue.categories.manage'])
            <a href="{{ route('class-fees.index', ['year' => $year, 'class_room_id' => $filters['class_room_id'] ?? null]) }}" class="text-sm font-semibold text-indigo-700 underline">Back to payment status</a>
            @endcanany
        </div>
    </x-slot>
    <div class="max-w-7xl mx-auto py-8 space-y-5" x-data="bulkClassFeeReversals(@js($candidates), @js($filters['class_room_id'] ?? ''), @js(old('lines', [])))">
        <style>
            .fee-reversal-layout { display:grid; grid-template-columns:minmax(0,1fr); gap:20px; }
            .fee-reversal-warning { background:#fff7ed; color:#9a3412; border:1px solid #fed7aa; border-radius:8px; padding:16px; font-size:13px; line-height:1.6; }
            .fee-reversal-total { display:flex; flex-wrap:wrap; gap:20px; background:#fff7f7; border-top:1px solid #fee2e2; padding:20px; font-size:13px; }
            .fee-reversal-submit { background:#b91c1c; color:#fff; border-radius:7px; padding:12px 20px; font-size:14px; font-weight:600; }
            .fee-reversal-submit:hover { background:#991b1b; }
            .fee-reversal-submit:disabled { opacity:.45; cursor:not-allowed; }
            .fee-reversal-receipt { margin-top:6px; border-left:2px solid #e2e8f0; padding-left:8px; font-size:12px; color:#64748b; }
            @media(min-width:1024px) { .fee-reversal-layout { grid-template-columns:minmax(0,1fr) minmax(0,2fr); } }
        </style>
        <div class="flex flex-wrap items-center gap-3 text-sm">
            @can('revenue.add')<a href="{{ route('class-fees.bulk', ['year' => $year, 'class_room_id' => $filters['class_room_id'] ?? null, 'include_inactive' => $filters['include_inactive'] ?? 0]) }}" class="rounded-md border border-gray-200 bg-white px-4 py-2 font-semibold text-indigo-700">Collect payments</a>@endcan
            <span class="rounded-md px-4 py-2 font-semibold text-white" style="background:#b91c1c;" aria-current="page">Make unpaid</span>
        </div>
        @if(session('status'))<div role="status" class="rounded-lg bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if($errors->any())<div role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <form method="GET" class="rounded-lg border border-gray-200 bg-white p-5 flex flex-wrap items-end gap-4">
            <input type="hidden" name="mode" value="unpaid"><input type="hidden" name="class_room_id" :value="classId">
            <div><label for="unpaid-year" class="block text-sm font-medium text-gray-700">Fee year</label><input id="unpaid-year" type="number" name="year" value="{{ $year }}" min="2000" max="2099" required class="mt-1 rounded-md border-gray-300 w-32"></div>
            <label class="flex items-center gap-2 text-sm text-gray-600 mb-2"><input type="checkbox" name="include_inactive" value="1" @checked($filters['include_inactive'] ?? false) class="rounded border-gray-300"> Include inactive / alumni</label>
            <x-secondary-button type="submit" x-bind:disabled="lines.length > 0">Load year</x-secondary-button>
            <p class="text-xs text-gray-500">Save or clear the reversal list before changing the year.</p>
        </form>
        <div class="fee-reversal-layout">
            <section class="rounded-lg bg-white border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-5 space-y-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">1. Choose paid students &amp; fees</h3>
                    <div><label for="unpaid-class" class="block text-sm font-medium text-gray-700">Class</label><select id="unpaid-class" x-model="classId" @change="selected = []" class="mt-1 w-full rounded-md border-gray-300"><option value="">All classes</option>@foreach($classes as $option)<option value="{{ $option->id }}">{{ $option->name }}</option>@endforeach</select></div>
                    <div><label for="unpaid-search" class="block text-sm font-medium text-gray-700">Search students</label><input id="unpaid-search" type="search" x-model.debounce.200ms="query" placeholder="Name or admission number" class="mt-1 w-full rounded-md border-gray-300"></div>
                    <div><label for="unpaid-term" class="block text-sm font-medium text-gray-700">Fees to make unpaid</label><select id="unpaid-term" x-model="installment" @change="selected = []" class="mt-1 w-full rounded-md border-gray-300"><option value="exam">All exam terms</option><option value="term:1">Term 1 only</option><option value="term:2">Term 2 only</option><option value="term:3">Term 3 only</option><option value="facility">Facility fee only</option><option value="all">Exam terms &amp; facility fee</option></select></div>
                    <p class="text-xs text-gray-500">Shows fees with recorded payments, including partially paid fees. All active receipts for each selected fee will be cancelled, including any pending cheque on that fee. Former-class payments remain under their original class.</p>
                    <label class="flex items-center gap-2 text-sm font-semibold text-indigo-700"><input type="checkbox" :checked="allSelected" @change="toggleAll($event.target.checked)" class="rounded border-gray-300 text-indigo-600">Select all available students <span x-text="'(' + selectable.length + ')'"></span></label>
                </div>
                <div class="max-h-96 overflow-y-auto divide-y divide-gray-100">
                    <template x-for="student in visible" :key="student.key">
                        <label class="flex gap-3 p-4 hover:bg-indigo-50 cursor-pointer">
                            <input type="checkbox" x-model="selected" :value="student.key" :disabled="!canSelect(student)" class="mt-1 rounded border-gray-300 text-indigo-600">
                            <span style="min-width:0;"><span class="block text-sm font-semibold text-gray-900" x-text="student.name"></span><span class="block text-xs text-gray-500" x-text="student.admission + ' · ' + student.class_name"></span>
                                <template x-for="cycle in matching(student)" :key="cycle.key"><span class="block mt-2 text-xs text-gray-600"><span x-text="cycle.label + ' · Paid Rs ' + money(cycle.paid)"></span><span x-show="cycle.blocked_reason" class="block mt-1 text-red-700" x-text="cycle.blocked_reason"></span></span></template>
                            </span>
                        </label>
                    </template>
                    <p x-show="visible.length === 0" class="p-5 text-sm text-gray-500">No paid fees match. Check the year, class, search or fee selection.</p>
                </div>
                <div class="p-4 border-t border-gray-100"><button type="button" @click="addSelected()" :disabled="selected.length === 0" class="w-full rounded-md bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">Add selected to reversal list <span x-text="selected.length ? '(' + selected.length + ')' : ''"></span></button></div>
            </section>
            <form method="POST" action="{{ route('class-fees.bulk.unpaid') }}" class="space-y-5" @submit="saving = true">
                @csrf
                <input type="hidden" name="lines_payload" :value="payload()"><input type="hidden" name="submission_id" value="{{ old('submission_id', $submissionId) }}"><input type="hidden" name="year" value="{{ $year }}">
                <section class="rounded-lg bg-white border border-gray-200 shadow-sm overflow-hidden">
                    <div class="p-5 flex flex-wrap justify-between gap-3 border-b border-gray-100"><h3 class="font-semibold text-gray-900">2. Review fees to make unpaid <span class="text-red-700" x-text="'(' + lines.length + ')'"></span></h3><button type="button" @click="lines = []" :disabled="saving" class="text-sm text-gray-500 underline">Clear list</button></div>
                    <div role="alert" x-show="message" x-cloak x-text="message" class="m-4 p-3 rounded bg-amber-50 text-sm text-amber-800"></div>
                    <div class="overflow-x-auto max-h-96">
                        <table class="w-full text-sm"><thead class="bg-gray-50 text-gray-600"><tr><th class="text-left p-3">Student / fee / payments</th><th class="text-right p-3">Paid to reverse (Rs)</th><th class="p-3"><span class="sr-only">Remove</span></th></tr></thead>
                            <tbody class="divide-y divide-gray-100"><template x-for="(line, index) in lines" :key="line.key"><tr>
                                <td class="p-3"><div class="font-semibold" x-text="line.name"></div><div class="text-xs text-gray-500" x-text="line.admission + ' · ' + line.class_name"></div><div class="text-xs text-indigo-700 mt-1" x-text="line.label"></div>
                                    <template x-for="receipt in line.payments" :key="receipt.source + ':' + receipt.id"><div class="fee-reversal-receipt"><span x-text="receipt.reference + ' · ' + receipt.date + ' · Rs ' + money(receipt.amount)"></span><span class="block" x-text="receipt.accounting"></span></div></template>
                                </td>
                                <td class="p-3 text-right font-semibold" style="white-space:nowrap;" x-text="money(line.paid)"></td>
                                <td class="p-3"><button type="button" @click="lines.splice(index, 1)" :disabled="saving" :aria-label="'Remove ' + line.name + ', ' + line.label" class="text-red-700 hover:underline">Remove</button></td>
                            </tr></template></tbody>
                        </table>
                        <div x-show="lines.length === 0" class="p-10 text-center"><p class="font-semibold text-gray-800">Your reversal list is empty</p><p class="mt-2 text-sm text-gray-500">Choose paid students and fees on the left. You can add students from different classes.</p></div>
                    </div>
                    <div class="fee-reversal-total"><span>Paid amount <strong x-text="'Rs ' + money(total)"></strong></span><span>Removed from revenue <strong x-text="'Rs ' + money(income)"></strong></span><span>Receipts <strong x-text="receiptCount"></strong></span><span x-show="pending > 0">Pending cheques cancelled <strong x-text="'Rs ' + money(pending)"></strong></span></div>
                </section>
                <section class="rounded-lg bg-white border border-gray-200 p-5 space-y-4">
                    <h3 class="font-semibold text-gray-900">3. Reason &amp; save</h3>
                    <div class="fee-reversal-warning">The listed payments will be cancelled and their fee balances reopened. Revenue receipts are removed from income and the ledger; history-only payments change the student’s fee balance. Bill numbers and audit history are retained. This corrects mistaken payments and does not record a cash refund.</div>
                    <div><label for="unpaid-reason" class="block text-sm font-medium text-gray-700">Reason for making these fees unpaid</label><textarea id="unpaid-reason" name="reason" minlength="3" maxlength="1000" rows="3" required class="mt-1 w-full rounded-md border-gray-300" placeholder="For example: payments were marked paid against the wrong students">{{ old('reason') }}</textarea></div>
                    <button type="submit" :disabled="lines.length === 0 || saving" class="fee-reversal-submit" x-text="saving ? 'Updating fees…' : 'Make ' + lines.length + ' fees unpaid'">Make selected fees unpaid</button>
                    <p class="text-xs text-gray-500">All selected fees are checked together. If a payment changed or needs individual review, the entire batch stays unchanged.</p>
                </section>
            </form>
        </div>
    </div>
</x-app-layout>
