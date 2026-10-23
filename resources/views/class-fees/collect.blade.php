@php
    $collectionConfig = [
        'url' => route('revenue.items.create'), 'type' => $type, 'query' => $filters['q'] ?? '',
        'classId' => $filters['class_room_id'] ?? '', 'year' => $year, 'allYears' => (bool) $filters['all_years'],
        'includeInactive' => (bool) ($filters['include_inactive'] ?? false), 'studentId' => $selectedStudent?->id,
        'amount' => old('amount', $selectedStatus['collectable'] ?? ''), 'date' => old('paid_at', today()->toDateString()),
        'method' => old('payment_method', 'cash'), 'recordRevenue' => (bool) old('record_revenue', true),
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-xl font-semibold text-gray-900">Collect {{ $feeLabel }}</h2><p class="mt-1 text-sm text-gray-600">Search a student, review their dues, and save or print the payment bill.</p></div>
            <a class="text-sm font-semibold text-indigo-700 underline" href="{{ route('class-fees.index', ['type' => $type]) }}">View fee register</a>
        </div>
    </x-slot>
    @include('class-fees.partials.collection-assets')
    <div class="fee-collection" x-data="feeCollection(@js($collectionConfig))">
        @if(session('status'))
            <div role="status" class="mb-5 rounded-lg bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}
                @if(session('class_fee_receipt_id'))<a class="ml-2 underline font-semibold" href="{{ route('revenue.items.receipt', session('class_fee_receipt_id')) }}">View / print bill</a>@endif
            </div>
        @endif
        @if($errors->any())<div role="alert" class="mb-5 rounded-lg bg-red-50 p-4 text-sm text-red-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <div class="fee-collection-grid">
            <section class="fee-payment-card" aria-labelledby="fee-payment-title">
                <h3 class="fee-card-title" id="fee-payment-title">Payment Details</h3>
                <div class="fee-field">
                    <label for="fee-category">Payment Category</label>
                    <select id="fee-category" class="fee-input" @change="categoryChanged($event.target.value)">
                        @foreach(['exam' => 'Exam Fee', 'facility' => 'Facility Fee'] as $kind => $label)<option value="fee:{{ $kind }}" @selected($type === $kind)>{{ $label }}</option>@endforeach
                        @foreach($categories->whereNull('class_fee_type') as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                    </select>
                    <p class="fee-hint">Change the category to collect another fee for this student.</p>
                </div>
                <form method="GET" action="{{ route('revenue.items.create') }}" id="fee-student-search">
                    <input type="hidden" name="quick" value="{{ $type }}">
                    <div class="fee-field">
                        <label for="fee-search">Student</label>
                        <div class="fee-search-row">
                            <div class="fee-search-wrap" @click.outside="open = false">
                                <input id="fee-search" class="fee-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" x-model="query" @input="queueSearch()" @keydown="keydown($event)" @focus="if(results.length) open = true" placeholder="Search by name, admission no, phone" maxlength="100" autocomplete="off" role="combobox" aria-autocomplete="list" aria-controls="fee-student-suggestions" :aria-expanded="open.toString()" :aria-activedescendant="highlighted >= 0 ? 'fee-suggestion-' + highlighted : null">
                                <div id="fee-student-suggestions" class="fee-suggestions" role="listbox" x-show="open" x-cloak>
                                    <template x-for="(student, index) in results" :key="student.id">
                                        <button type="button" role="option" :id="'fee-suggestion-' + index" :aria-selected="(index === highlighted).toString()" class="fee-suggestion" :class="index === highlighted ? 'is-highlighted' : ''" @click="choose(student)"><strong x-text="student.name"></strong><span x-text="student.class + ' · ' + student.admission_number"></span></button>
                                    </template>
                                    <div class="fee-suggestion" x-show="!results.length">No matching students.</div>
                                </div>
                                <div class="fee-search-status" role="status" x-show="loading" x-cloak>Searching students…</div>
                                <div class="fee-search-status" role="alert" x-show="error" x-text="error" x-cloak></div>
                            </div>
                            <select id="fee-class" aria-label="Class" class="fee-input" name="class_room_id" x-model="classId" @change="classChanged()"><option value="">All classes</option>@foreach($classes as $option)<option value="{{ $option->id }}" @selected((int) ($filters['class_room_id'] ?? 0) === $option->id)>{{ $option->name }}</option>@endforeach</select>
                        </div>
                        @if($selectedStudent)
                            <div class="fee-selected-student"><div><strong>{{ $selectedStudent->name }}</strong><p class="fee-hint">{{ $selectedStudent->admission_number }} · {{ $selectedStudent->classRoom?->name ?? 'No class assigned' }}</p></div><a class="fee-link" href="{{ route('revenue.items.create', array_merge($filters, ['quick' => $type, 'student_id' => null, 'cycle' => null, 'q' => null])) }}">Change</a></div>
                        @endif
                        <div class="fee-search-fallback"><span class="fee-hint">Suggestions appear as you type.</span><button type="submit" class="fee-link">Search students</button></div>
                    </div>
                    <details class="fee-filters"><summary>Installment period &amp; student filters</summary><div class="fee-filter-content">
                        <div><label for="fee-year" class="fee-field-label">Installment year</label><input id="fee-year" class="fee-input" type="number" name="year" min="2000" max="2099" value="{{ $year }}" x-model="year" required></div>
                        <input type="hidden" name="all_years" value="0"><label class="fee-check"><input type="checkbox" name="all_years" value="1" x-model="allYears" @checked($filters['all_years'])> Include earlier years</label>
                        <label class="fee-check"><input type="checkbox" name="include_inactive" value="1" x-model="includeInactive" @checked($filters['include_inactive'] ?? false)> Include inactive / alumni</label>
                        @if($selectedStudent)<input type="hidden" name="student_id" value="{{ $selectedStudent->id }}">@endif
                        <button class="fee-link" type="submit">Apply filters</button>
                    </div></details>
                </form>
                @if(! $selectedStudent && !empty($filters['q']))
                    <div class="fee-field"><div class="fee-hint">{{ $students->total() }} matching students</div>
                        @foreach($students as $student)<a class="fee-suggestion" href="{{ route('revenue.items.create', array_merge($filters, ['quick' => $type, 'student_id' => $student->id, 'cycle' => null])) }}"><strong>{{ $student->name }}</strong><span>{{ $student->classRoom?->name }} · {{ $student->admission_number }}</span></a>@endforeach
                        {{ $students->links() }}
                    </div>
                @endif
                @if($selectedCycle && $selectedStatus['collectable'] > 0)
                    <h4 class="fee-field-label">Record {{ strtolower($feeLabel) }} payment</h4>
                    <div class="fee-field"><label for="fee-installment">Installment to pay</label>
                        <select id="fee-installment" class="fee-input" onchange="window.location.assign(this.value)">
                            @foreach($installments as $key => $entry)
                                @if($entry['status']['collectable'] > 0)<option value="{{ route('revenue.items.create', array_merge($filters, ['quick' => $type, 'student_id' => $selectedStudent->id, 'cycle' => $key])) }}" @selected($selectedCycle['key'] === $key)>{{ isset($entry['cycle']['term']) ? 'Term '.$entry['cycle']['term'].' · ' : '' }}{{ $entry['cycle']['due']->format('d M Y') }} · {{ $entry['cycle']['schedule']->classRoom?->name }} · Rs {{ number_format($entry['status']['collectable'], 2) }}</option>@endif
                            @endforeach
                        </select>
                    </div>
                    @include('class-fees.partials.collection-payment-form')
                @elseif($selectedStudent)
                    <div class="fee-empty">{{ $installments->isEmpty() ? 'No applicable '.strtolower($feeLabel).' installments for this student and period.' : ($installments->contains(fn ($entry) => $entry['status']['collectable'] > 0) ? 'Choose an installment in the billing summary.' : ($installments->sum(fn ($entry) => $entry['status']['pending']) > 0 ? 'The remaining balance is reserved by pending cheques.' : 'All listed installments are settled.')) }}</div>
                @else
                    <div class="fee-empty">Find student: type a name or admission number and select a suggestion to enable payment.</div>
                @endif
            </section>
            <aside class="fee-billing-card" aria-labelledby="fee-billing-title">
                <h3 class="fee-card-title" id="fee-billing-title"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 3h12v18l-3-2-3 2-3-2-3 2V3Z M4 7H2V3h6 M11 7h6 M11 11h6 M11 15h3"/></svg>Billing Summary</h3>
                <dl><div class="fee-billing-row"><dt>Category:</dt><dd>{{ $feeLabel }}</dd></div><div class="fee-billing-row"><dt>Student:</dt><dd>{{ $selectedStudent?->name ?? '—' }}</dd></div><div class="fee-billing-row"><dt>Date:</dt><dd x-text="date ? date.split('-').reverse().join('-') : '—'">{{ today()->format('d-m-Y') }}</dd></div></dl>
                <div class="fee-total"><small>TOTAL AMOUNT</small><span>Rs </span><strong x-text="money(amount)">{{ number_format($selectedStatus['collectable'] ?? 0, 2) }}</strong></div>
                <div class="fee-due-summary"><h4>{{ $feeLabel }} due</h4><strong>Rs {{ number_format($installments->sum(fn ($entry) => $entry['status']['due']), 2) }}</strong>
                    @if($selectedStudent)<p class="fee-hint">{{ $filters['all_years'] ? 'All installments through '.$year : $year.' installments' }} · Applicable earlier class dues included.</p>@endif
                </div>
                @if($selectedStudent)
                    <dl><div class="fee-billing-row"><dt>Payments recorded:</dt><dd>Rs {{ number_format($installments->sum(fn ($entry) => $entry['status']['paid']), 2) }}</dd></div><div class="fee-billing-row"><dt>Pending cheques:</dt><dd>Rs {{ number_format($installments->sum(fn ($entry) => $entry['status']['pending']), 2) }}</dd></div></dl>
                    <div class="fee-term-list">
                    @foreach($installments as $key => $entry)
                        <div class="fee-term"><div class="fee-term-heading"><strong>{{ isset($entry['cycle']['term']) ? 'Term '.$entry['cycle']['term'] : $feeLabel }}</strong><span>{{ $entry['status']['status'] }}</span></div><p>{{ $entry['cycle']['due']->format('d M Y') }}@if(isset($entry['cycle']['end'])) – {{ $entry['cycle']['end']->format('d M Y') }}@endif · {{ $entry['cycle']['schedule']->classRoom?->name }}</p><div class="fee-term-heading"><span>Balance Rs {{ number_format($entry['status']['remaining'], 2) }}</span>@if($entry['status']['collectable'] > 0)<a class="fee-link" href="{{ route('revenue.items.create', array_merge($filters, ['quick' => $type, 'student_id' => $selectedStudent->id, 'cycle' => $key])) }}">{{ $selectedCycle && $selectedCycle['key'] === $key ? 'Selected' : 'Collect' }}</a>@endif</div></div>
                    @endforeach
                    </div>
                @else<p class="fee-empty">Select a student to see their {{ strtolower($feeLabel) }} dues here.</p>@endif
            </aside>
        </div>
    </div>
</x-app-layout>
