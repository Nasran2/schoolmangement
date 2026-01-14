<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reports') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <a href="{{ route('reports.revenue') }}" class="block border rounded-lg p-4 hover:bg-gray-50">
                            <div class="font-semibold">Revenue Report</div>
                            <div class="text-sm text-gray-600">Filter by date range and category.</div>
                        </a>
                        <a href="{{ route('reports.expense') }}" class="block border rounded-lg p-4 hover:bg-gray-50">
                            <div class="font-semibold">Expense Report</div>
                            <div class="text-sm text-gray-600">Filter by date range and category.</div>
                        </a>
                        <a href="{{ route('reports.financial') }}" class="block border rounded-lg p-4 hover:bg-gray-50">
                            <div class="font-semibold">Financial Summary</div>
                            <div class="text-sm text-gray-600">Revenue vs expenses and net profit.</div>
                        </a>

                        <a href="{{ route('reports.teacher_epf') }}" class="block border rounded-lg p-4 hover:bg-gray-50">
                            <div class="font-semibold">Teacher EPF Report</div>
                            <div class="text-sm text-gray-600">EPF deductions from teacher salary payments.</div>
                        </a>
                        <a href="{{ route('reports.teacher_etf') }}" class="block border rounded-lg p-4 hover:bg-gray-50">
                            <div class="font-semibold">Teacher ETF Report</div>
                            <div class="text-sm text-gray-600">ETF deductions from teacher salary payments.</div>
                        </a>
                        <a href="{{ route('reports.student_due') }}" class="block border rounded-lg p-4 hover:bg-gray-50">
                            <div class="font-semibold">Student Due Amount</div>
                            <div class="text-sm text-gray-600">Monthly fee due amounts by student/class.</div>
                        </a>

                        <a href="{{ route('reports.fee_collection_summary') }}" class="block border rounded-lg p-4 hover:bg-gray-50">
                            <div class="font-semibold">Fee Collection Summary</div>
                            <div class="text-sm text-gray-600">Totals grouped by day or month (from revenue records).</div>
                        </a>
                        <a href="{{ route('reports.fee_collection_by_class') }}" class="block border rounded-lg p-4 hover:bg-gray-50">
                            <div class="font-semibold">Fee Collection by Class</div>
                            <div class="text-sm text-gray-600">Total fee collection grouped by class room.</div>
                        </a>
                        <a href="{{ route('reports.fee_collection_by_category') }}" class="block border rounded-lg p-4 hover:bg-gray-50">
                            <div class="font-semibold">Fee Collection by Category</div>
                            <div class="text-sm text-gray-600">Collection totals grouped by revenue category.</div>
                        </a>
                        <a href="{{ route('reports.fee_collection_vs_expected') }}" class="block border rounded-lg p-4 hover:bg-gray-50">
                            <div class="font-semibold">Collected vs Expected (Monthly Fees)</div>
                            <div class="text-sm text-gray-600">Month-wise expected vs collected using allocations.</div>
                        </a>
                        <a href="{{ route('reports.student_due_aging') }}" class="block border rounded-lg p-4 hover:bg-gray-50">
                            <div class="font-semibold">Student Due Aging</div>
                            <div class="text-sm text-gray-600">Bucket students by pending months and total due.</div>
                        </a>
                        <a href="{{ route('reports.student_top_due') }}" class="block border rounded-lg p-4 hover:bg-gray-50">
                            <div class="font-semibold">Top Due Students</div>
                            <div class="text-sm text-gray-600">Highest due students for quick follow-up.</div>
                        </a>

                        <a href="{{ route('reports.fee_discounts') }}" class="block border rounded-lg p-4 hover:bg-gray-50">
                            <div class="font-semibold">Discount/Waiver Report</div>
                            <div class="text-sm text-gray-600">Requires discount tracking fields.</div>
                        </a>
                        <a href="{{ route('reports.fee_refunds') }}" class="block border rounded-lg p-4 hover:bg-gray-50">
                            <div class="font-semibold">Refund/Cancellation Report</div>
                            <div class="text-sm text-gray-600">Requires refund/cancellation tracking fields.</div>
                        </a>

                        <a href="{{ route('reports.seminars_collection') }}" class="block border rounded-lg p-4 hover:bg-gray-50">
                            <div class="font-semibold">Seminars Collection</div>
                            <div class="text-sm text-gray-600">Attendance & payments summary for seminars.</div>
                        </a>
                        <a href="{{ route('reports.extra_classes_collection') }}" class="block border rounded-lg p-4 hover:bg-gray-50">
                            <div class="font-semibold">Extra Classes Collection</div>
                            <div class="text-sm text-gray-600">Payments summary for extra classes.</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
