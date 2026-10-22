@if(! empty($classFeeSummaries))
    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($classFeeSummaries as $feeType => $summary)
            <div data-fee-widget="{{ $feeType }}" class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">{{ $feeType === 'exam' ? 'Exam Fees' : 'Facility Fees' }}</h3>
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div><div class="text-sm text-gray-500">Collected · {{ $dashboardRange['label'] }}</div><div class="mt-1 text-xl font-semibold text-green-700">Rs {{ number_format($summary['collected'], 2) }}</div></div>
                    <div><div class="text-sm text-gray-500">Outstanding due now</div><div class="mt-1 text-xl font-semibold text-red-700">Rs {{ number_format($summary['due'], 2) }}</div></div>
                </div>
                <p class="mt-3 text-sm text-gray-600">{{ $summary['unpaid_students'] }} student{{ $summary['unpaid_students'] === 1 ? '' : 's' }} with unpaid installments across all years.</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    @can('revenue.add')<a class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700" href="{{ route('class-fees.index', ['type' => $feeType]) }}">Collect {{ $feeType === 'exam' ? 'exam fee' : 'facility fee' }}</a>@endcan
                    @canany(['class_fees.view', 'revenue.add', 'revenue.manage', 'revenue.categories.manage'])<a class="rounded-md border px-3 py-2 text-sm font-semibold text-indigo-700" href="{{ route('class-fees.index', ['type' => $feeType, 'status' => 'unpaid', 'all_years' => 1]) }}">View unpaid students</a>@endcanany
                </div>
            </div>
        @endforeach
    </div>
@endif
