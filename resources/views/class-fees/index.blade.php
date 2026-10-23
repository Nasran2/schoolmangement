<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">{{ $reportTitle }} — Payments &amp; Dues</h2>
                <p class="mt-1 text-sm text-gray-600">Collect fees individually or in bulk. Term fees are due from the starting date; balances remain due after the term ends.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('revenue.add')<a class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700" href="{{ route('class-fees.bulk', ['class_room_id' => $class?->id, 'year' => $year]) }}">Bulk payments</a>@endcan
                @foreach(\App\Models\ClassFeeSchedule::TYPES as $kind => $label)
                    <a class="rounded-md border px-3 py-2 text-sm {{ $type === $kind ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700' }}" href="{{ route('class-fees.index', ['type' => $kind, 'class_room_id' => $class?->id, 'year' => $year, 'all_years' => $filters['all_years'] ?? 0]) }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    </x-slot>

    <div class="py-8 space-y-6 max-w-7xl mx-auto">
        @if(session('status'))
            <div role="status" class="rounded-lg bg-green-50 p-4 text-sm text-green-800">
                {{ session('status') }}
                @if(session('class_fee_receipt_id') && auth()->user()->can('revenue.manage'))
                    <a class="ml-2 underline font-semibold" href="{{ route('revenue.items.receipt', session('class_fee_receipt_id')) }}">View / print receipt</a>
                @endif
            </div>
        @endif
        @if($errors->any())
            <div role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-800">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <form method="GET" action="{{ route('class-fees.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 rounded-lg bg-white p-5 shadow-sm"
              x-data="{ selectedExamDates: @js($selectedExamDates), allExamDates: @js($examOptions->all()) }">
            <input type="hidden" name="exam_selection" value="1">
            <input type="hidden" name="selection_scope" value="{{ $filters['selection_scope'] }}">
            <input type="hidden" name="type" value="{{ $type }}">
            <div>
                <x-input-label for="class_room_id" value="Class" />
                <select name="class_room_id" id="class_room_id" class="mt-1 w-full rounded-md border-gray-300">
                    <option value="">All classes — overview</option>
                    @foreach($classes as $option)<option value="{{ $option->id }}" @selected($class?->id === $option->id)>{{ $option->name }}{{ $option->active ? '' : ' (inactive)' }}</option>@endforeach
                </select>
            </div>
            <div>
                <x-input-label for="year" value="Installment year" />
                <x-text-input type="number" id="year" name="year" min="2000" max="2099" :value="$year" class="mt-1 w-full" />
                <label class="mt-2 text-xs text-gray-600 flex items-center gap-2"><input type="checkbox" name="all_years" value="1" @checked($filters['all_years'] ?? false)> All years (including earlier dues)</label>
            </div>
            <div>
                <x-input-label for="status" value="Payment status" />
                <select name="status" id="status" class="mt-1 w-full rounded-md border-gray-300">
                    @foreach(['all' => 'All students', 'unpaid' => 'Unpaid / balance due', 'paid' => 'All due installments settled', 'partial' => 'Some payments made', 'pending' => 'Pending cheque'] as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['status'] ?? 'all') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="q" value="Student / admission number" />
                <x-text-input id="q" name="q" :value="$filters['q'] ?? ''" class="mt-1 w-full" />
            </div>
            <div class="flex flex-col justify-end gap-2">
                <label class="text-xs text-gray-600 flex items-center gap-2"><input type="checkbox" name="include_inactive" value="1" @checked($filters['include_inactive'] ?? false)> Include inactive / alumni</label>
                <label class="text-xs text-gray-600 flex items-center gap-2"><input type="checkbox" name="include_former" value="1" @checked($filters['include_former'] ?? false)> Include former class students</label>
                <x-primary-button>Apply filters</x-primary-button>
                @can('reports.download')
                    <button type="submit" name="pdf" value="1" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-700">Download PDF</button>
                @endcan
            </div>
            <div class="sm:col-span-2 lg:col-span-5 border-t border-gray-100 pt-4 space-y-3">
                @if($type === 'exam')
                    <fieldset>
                        <legend class="text-sm font-semibold text-gray-800 mb-2">Exam fee installments</legend>
                        <div class="flex flex-wrap items-center gap-3 mb-3">
                            <button type="button" class="text-xs font-semibold text-indigo-700 underline" @click="selectedExamDates = allExamDates.slice()">Select all exams</button>
                            <button type="button" class="text-xs font-semibold text-gray-600 underline" @click="selectedExamDates = []">Clear exams</button>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            @forelse($examOptions as $examDate)
                                <label class="inline-flex items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                                    <input type="checkbox" name="exam_dates[]" value="{{ $examDate }}" x-model="selectedExamDates" @checked(in_array($examDate, $selectedExamDates, true)) class="rounded border-gray-300 text-indigo-600">
                                    {{ $examLabels[$examDate] }}
                                </label>
                            @empty
                                <p class="text-sm text-gray-500">No exam installments are scheduled for this class and period.</p>
                            @endforelse
                        </div>
                    </fieldset>
                    <input type="hidden" name="include_facility" value="0">
                    <label class="inline-flex items-center gap-2 rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-800">
                        <input type="checkbox" name="include_facility" value="1" @checked($includeFacility) class="rounded border-gray-300 text-indigo-600">
                        Include facility fee
                    </label>
                    <p class="text-xs text-gray-500">All exam dates and facility fees are selected by default. The list, totals, payment status, and PDF use only the fees you select. Changing the class or year selects all of its exam dates again.</p>
                @else
                    <input type="hidden" name="include_facility" value="1">
                    <p class="text-sm text-gray-600">Facility fee installments for the selected period are included.</p>
                @endif
            </div>
        </form>
        <p class="text-xs text-gray-500">The year filters installment due dates, including payments made later. Term fees become due at the start; unpaid terms become overdue after their ending date. Future terms do not count as due. Facility fees apply from the student’s joining year, regardless of the joining month. Students joining during a term owe that term; completed terms before admission do not apply. Only current class members are shown by default. Select Include former class students to view earlier dues and payments.</p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-lg bg-white p-5 shadow-sm"><div class="text-sm text-gray-500">Payments recorded for selected {{ ($filters['all_years'] ?? false) ? 'installments' : $year.' installments' }}</div><div class="mt-1 text-2xl font-semibold">Rs {{ number_format($tracker['paid'], 2) }}</div></div>
            <div class="rounded-lg bg-white p-5 shadow-sm"><div class="text-sm text-gray-500">Outstanding due now</div><div class="mt-1 text-2xl font-semibold text-red-700">Rs {{ number_format($tracker['due'], 2) }}</div></div>
            <div class="rounded-lg bg-white p-5 shadow-sm"><div class="text-sm text-gray-500">Students with unpaid installments</div><div class="mt-1 text-2xl font-semibold">{{ $tracker['unpaid_students'] }}</div></div>
        </div>

        <section aria-label="Fee dues by term">
            <style>
                .class-fee-due-cards { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; }
                .class-fee-due-card { border-top: 3px solid #6366f1; min-width: 0; }
                .class-fee-due-card[data-fee="facility"] { border-top-color: #0d9488; }
                .class-fee-due-amount { overflow-wrap: anywhere; font-variant-numeric: tabular-nums; }
                @media (min-width: 640px) { .class-fee-due-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
                @media (min-width: 1024px) { .class-fee-due-cards { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
            </style>
            <div class="class-fee-due-cards">
                @foreach($dueBreakdown as $feeKey => $card)
                    <div class="class-fee-due-card rounded-lg bg-white p-5 shadow-sm" data-fee="{{ $feeKey }}">
                        <h3 class="text-sm font-semibold text-gray-700">{{ $card['label'] }}</h3>
                        <div class="class-fee-due-amount mt-1 text-2xl font-semibold {{ $card['due'] > 0 ? 'text-red-700' : 'text-gray-900' }}">Rs {{ number_format($card['due'], 2) }}</div>
                        <p class="mt-2 text-xs text-gray-500">{{ $card['selected'] ? 'Outstanding due now' : 'No selected fees in this period' }}</p>
                    </div>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-gray-500">These balances follow the filters above. Future fees are excluded until their starting date.</p>
        </section>

        @if($class && $tracker['cycles']->isEmpty())
            <div class="rounded-lg bg-white p-6 shadow-sm text-sm text-gray-600">
                No installments are selected for {{ $class->name }} in this period. Select exam dates or include facility fees above.
                @can('classrooms.update')<a class="text-indigo-700 underline" href="{{ route('classrooms.edit', $class) }}">Set the fee and first due date</a>@endcan
            </div>
        @endif

        @if($class && $selectedStudent && $selectedCycle && $selectedStatus && $selectedStatus['eligible'])
            @can('revenue.add')
                <div id="collect-fee" class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold">Collect {{ \App\Models\ClassFeeSchedule::TYPES[$selectedCycle['schedule']->fee_type] }} — {{ $selectedStudent->name }}</h3>
                    <p class="mt-1 mb-5 text-sm text-gray-600">{{ $class->name }} · Due {{ $selectedCycle['due']->format('d M Y') }} · Fee Rs {{ number_format($selectedCycle['amount'], 2) }} · Paid Rs {{ number_format($selectedStatus['paid'], 2) }} · Pending Rs {{ number_format($selectedStatus['pending'], 2) }}</p>
                    @if($selectedStatus['collectable'] > 0)
                        @include('class-fees.partials.payment-form')
                        @if($unassigned->isNotEmpty())
                            <details class="mt-5 border-t pt-4">
                                <summary class="cursor-pointer text-sm font-semibold text-indigo-700">Assign an earlier receipt to this installment</summary>
                                <p class="mt-2 text-sm text-gray-600">Use this for an exam or facility fee already recorded in Revenue. Assigning a receipt does not collect money again.</p>
                                <form method="POST" action="{{ route('class-fees.store', ['classroom' => $class->id, 'type' => $selectedCycle['schedule']->fee_type]) }}" class="mt-3 flex flex-wrap items-center gap-3">
                                    @csrf
                                    <input type="hidden" name="student_id" value="{{ $selectedStudent->id }}"><input type="hidden" name="cycle" value="{{ $selectedCycle['key'] }}"><input type="hidden" name="submission_id" value="{{ $submissionId }}">
                                    <select aria-label="Earlier fee receipt" name="existing_revenue_id" required class="rounded-md border-gray-300"><option value="">Choose an unassigned receipt</option>@foreach($unassigned as $receipt)<option value="{{ $receipt->id }}">{{ $receipt->bill_no ?: '#'.$receipt->id }} · {{ $receipt->paid_at->format('Y-m-d') }} · Rs {{ number_format($receipt->amount, 2) }}</option>@endforeach</select>
                                    <x-primary-button>Assign receipt</x-primary-button>
                                </form>
                            </details>
                        @endif
                    @else
                        <p class="text-sm font-semibold text-green-700">{{ $selectedStatus['remaining'] < 0.01 ? 'This installment is fully settled.' : 'The remaining amount is reserved by a pending cheque.' }}</p>
                    @endif
                </div>
            @endcan
        @endif

        @if(! $class)
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @forelse($classSummaries as $summary)
                    <a class="rounded-lg bg-white p-5 shadow-sm border border-gray-100 hover:border-indigo-400" href="{{ route('class-fees.index', array_merge(['type' => $type, 'class_room_id' => $summary['class']->id, 'year' => $year, 'status' => $filters['status'] ?? 'all', 'all_years' => $filters['all_years'] ?? 0, 'include_inactive' => $filters['include_inactive'] ?? 0, 'q' => $filters['q'] ?? ''], $selectionQuery)) }}">
                        <h3 class="font-semibold text-gray-900">{{ $summary['class']->name }}</h3>
                        <div class="mt-2 text-sm text-gray-600">Payments recorded: Rs {{ number_format($summary['paid'], 2) }}</div>
                        <div class="mt-1 text-sm font-semibold text-red-700">Due: Rs {{ number_format($summary['due'], 2) }} · {{ $summary['unpaid_students'] }} unpaid students</div>
                        <div class="mt-3 text-sm font-semibold text-indigo-700">View installments / collect →</div>
                    </a>
                @empty
                    <p class="rounded-lg bg-white p-5 text-sm text-gray-600">No classes match this fee and filter. Configure the fee and term calendar in Class Rooms.</p>
                @endforelse
            </div>
        @else
        <div class="rounded-lg bg-white p-5 shadow-sm overflow-x-auto">
            <h3 class="text-lg font-semibold mb-4">{{ $class?->name ?? 'Class fees' }} — Installment status</h3>
            <table class="min-w-full text-sm divide-y divide-gray-200">
                <thead class="bg-gray-50"><tr>
                    <th class="p-3 text-left">Student</th>
                    @foreach($tracker['cycles'] as $cycle)<th class="p-3 text-left whitespace-nowrap"><div>{{ \App\Models\ClassFeeSchedule::TYPES[$cycle['schedule']->fee_type] }}</div>@if(isset($cycle['term']))<div class="text-indigo-700">Term {{ $cycle['term'] }}</div>@endif{{ $cycle['due']->format('d M Y') }}@if(isset($cycle['end']))<div class="text-xs font-normal text-gray-500">to {{ $cycle['end']->format('d M Y') }}</div>@endif<div class="text-xs font-normal text-gray-500">Rs {{ number_format($cycle['amount'], 2) }}</div></th>@endforeach
                    <th class="p-3 text-left whitespace-nowrap">Installments paid</th><th class="p-3 text-right">Due now (Rs)</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        <tr>
                            <td class="p-3"><div class="font-semibold">{{ $row['student']->name }}</div><div class="text-xs text-gray-500">{{ $row['student']->admission_number }}{{ $row['student']->class_room_id !== $class?->id ? ' · Former class' : '' }}</div></td>
                            @foreach($row['installments'] as $key => $installment)
                                <td class="p-3 whitespace-nowrap">
                                    <span class="font-semibold {{ $installment['status'] === 'Paid' ? 'text-green-700' : ($installment['due'] > 0 ? 'text-red-700' : 'text-gray-600') }}">{{ $installment['status'] }}</span>
                                    @if($installment['eligible'])
                                        <div class="text-xs text-gray-500">Paid {{ number_format($installment['paid'], 2) }} / {{ number_format($tracker['cycles'][$key]['amount'], 2) }}</div>
                                        @if($installment['pending'] > 0)<div class="text-xs text-amber-700">Pending {{ number_format($installment['pending'], 2) }}</div>@endif
                                        @if($installment['waived'] > 0)<div class="text-xs text-gray-600">Waived {{ number_format($installment['waived'], 2) }}</div>@endif
                                        @can('revenue.add')
                                            @if($installment['collectable'] > 0 && $row['student']->active && ! $row['student']->alumni)
                                                <a class="text-xs font-semibold text-indigo-700 underline" href="{{ route('class-fees.index', ['type' => $tracker['cycles'][$key]['schedule']->fee_type, 'class_room_id' => $class->id, 'year' => $tracker['cycles'][$key]['due']->year, 'student_id' => $row['student']->id, 'cycle' => $key, 'include_former' => $filters['include_former'] ?? 0, 'include_inactive' => $filters['include_inactive'] ?? 0]) }}#collect-fee">{{ $tracker['cycles'][$key]['due']->gt(today()) ? 'Pay in advance' : 'Collect' }}</a>
                                            @endif
                                        @endcan
                                    @endif
                                    @can('revenue.manage')
                                        @foreach($installment['receipts'] as $receipt)
                                            @if(! $receipt->isCancelled())
                                                @php($receiptSource = $receipt instanceof \App\Models\Revenue ? 'revenue' : 'history')
                                                <div class="mt-1 text-xs">
                                                    @if($receiptSource === 'revenue')<a class="text-indigo-700 underline" href="{{ route('revenue.items.receipt', $receipt) }}">{{ $receipt->bill_no ?: '#'.$receipt->id }}</a>@else<span class="text-gray-500">History #{{ $receipt->id }} · no income</span>@endif
                                                    <a class="ml-1 text-indigo-700 underline" href="{{ route('class-fees.payments.edit', ['source' => $receiptSource, 'payment' => $receipt->id]) }}">Edit / delete</a>
                                                </div>
                                            @endif
                                        @endforeach
                                    @endcan
                                </td>
                            @endforeach
                            <td class="p-3 font-semibold">{{ $row['paid_count'] }} of {{ $row['due_count'] }} paid @if($row['waived_count'] > 0)<div class="text-xs font-normal text-gray-600">{{ $row['waived_count'] }} settled with waiver</div>@endif</td><td class="p-3 text-right font-mono">{{ number_format($row['due'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td class="p-5 text-gray-500" colspan="{{ $tracker['cycles']->count() + 3 }}">No students match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif
    </div>
</x-app-layout>
