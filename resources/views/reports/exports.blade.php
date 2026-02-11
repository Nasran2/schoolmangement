<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Advanced Exports</h2>
                <p class="text-gray-600 text-sm mt-1">CSV/Excel exports + download all PDFs as a bundle</p>
            </div>
            <a class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition" href="{{ route('reports.index') }}">← Back to Reports</a>
        </div>
    </x-slot>

    @php
        $base = request()->query();
        unset($base['download'], $base['excel'], $base['pdf'], $base['format']);
        $from = $preset['from'] ?? null;
        $to = $preset['to'] ?? null;
        $fromMonth = $preset['from_month'] ?? now()->format('Y-m');
        $toMonth = $preset['to_month'] ?? now()->format('Y-m');
    @endphp

    <div class="py-10 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow border border-gray-100 mb-6">
                <div class="border-b border-gray-200 px-6 py-5 bg-gradient-to-r from-indigo-50 to-blue-50">
                    <h3 class="text-lg font-semibold text-gray-800">Date Range</h3>
                    <p class="text-sm text-gray-600 mt-1">Pick a preset or a custom range. These params are reused for every export link below.</p>
                </div>
                <div class="p-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-7 gap-4">
                        <div class="md:col-span-2">
                            <x-input-label for="preset" :value="__('Preset')" class="font-semibold mb-1" />
                            <select id="preset" name="preset" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm">
                                @php $p = (string)($preset['preset'] ?? 'custom'); @endphp
                                <option value="today" @selected($p==='today')>Today</option>
                                <option value="yesterday" @selected($p==='yesterday')>Yesterday</option>
                                <option value="this_week" @selected($p==='this_week')>This week</option>
                                <option value="last_week" @selected($p==='last_week')>Last week</option>
                                <option value="this_month" @selected($p==='this_month')>This month</option>
                                <option value="last_month" @selected($p==='last_month')>Last month</option>
                                <option value="month" @selected($p==='month')>Pick month…</option>
                                <option value="custom" @selected($p==='custom')>Custom range</option>
                            </select>
                        </div>

                        <div>
                            <x-input-label for="month" :value="__('Month (for preset=month)')" class="font-semibold mb-1" />
                            <x-text-input id="month" name="month" type="month" class="mt-1 block w-full rounded-lg" :value="request('month')" />
                        </div>

                        <div>
                            <x-input-label for="from" :value="__('From')" class="font-semibold mb-1" />
                            <x-text-input id="from" name="from" type="text" data-datepicker placeholder="YYYY-MM-DD" class="mt-1 block w-full rounded-lg" :value="$from" />
                        </div>

                        <div>
                            <x-input-label for="to" :value="__('To')" class="font-semibold mb-1" />
                            <x-text-input id="to" name="to" type="text" data-datepicker placeholder="YYYY-MM-DD" class="mt-1 block w-full rounded-lg" :value="$to" />
                        </div>

                        <div class="md:col-span-2 flex items-end">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="include_salary" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ request()->boolean('include_salary') ? 'checked' : '' }}>
                                <span class="font-semibold">Include Salary Payments (Expense)</span>
                            </label>
                        </div>

                        <div class="flex items-end">
                            <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition shadow-sm">
                                Apply
                            </button>
                        </div>
                    </form>

                    @can('reports.download')
                        <div class="mt-5 flex flex-col sm:flex-row gap-3">
                            <a class="inline-flex items-center justify-center px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition shadow-sm" href="{{ route('reports.download_all', $base) }}">
                                Download all reports (PDF bundle)
                            </a>
                            <div class="text-sm text-gray-600 flex items-center">
                                Bundles only the reports you’re permitted to view.
                            </div>
                        </div>
                    @endcan
                </div>
            </div>

            <div class="bg-white rounded-lg shadow border border-gray-100">
                <div class="border-b border-gray-200 px-6 py-5 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <h3 class="text-lg font-semibold text-gray-800">Exports</h3>
                    <p class="text-sm text-gray-600 mt-1">Use CSV, Excel (XLSX), or PDF per report.</p>
                </div>

                <div class="p-6 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Report</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">PDF</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">CSV</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Excel</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @php
                                $links = [
                                    ['Revenue', 'reports.revenue', $base, 'reports.revenue.view'],
                                    ['Expense', 'reports.expense', $base, 'reports.expense.view'],
                                    ['Financial Summary', 'reports.financial', $base, 'reports.financial.view'],
                                    ['Daily Ledger', 'reports.daily_ledger', $base, 'reports.daily_ledger.view'],
                                    ['Cash Transactions', 'reports.cash_transactions', $base, 'reports.cash_transactions.view'],
                                    ['Bank Transactions', 'reports.bank_transactions', $base, 'reports.bank_transactions.view'],
                                    ['Cheque History', 'reports.cheque_history', $base, 'reports.cheque_history.view'],
                                    ['Teacher EPF', 'reports.teacher_epf', $base, 'reports.teacher_epf.view'],
                                    ['Company EPF', 'reports.company_epf', $base, 'reports.company_epf.view'],
                                    ['Company ETF', 'reports.teacher_etf', $base, 'reports.teacher_etf.view'],
                                    ['EPF/ETF Totals', 'reports.epf_etf_totals', $base, 'reports.epf_etf_totals.view'],
                                    ['Student Due', 'reports.student_due', $base, 'reports.student_due.view'],
                                    ['Fee Collection Summary', 'reports.fee_collection_summary', $base, 'reports.fee_collection_summary.view'],
                                    ['Fee Collection by Class', 'reports.fee_collection_by_class', $base, 'reports.fee_collection_by_class.view'],
                                    ['Fee Collection by Category', 'reports.fee_collection_by_category', $base, 'reports.fee_collection_by_category.view'],
                                    ['Student Due Aging', 'reports.student_due_aging', $base, 'reports.student_due_aging.view'],
                                    ['Top Due Students', 'reports.student_top_due', $base, 'reports.student_top_due.view'],
                                    ['Discount/Waivers', 'reports.fee_discounts', $base, 'reports.fee_discounts.view'],
                                    ['Refunds', 'reports.fee_refunds', $base, 'reports.fee_refunds.view'],
                                    ['Seminars Collection', 'reports.seminars_collection', $base, 'reports.seminars_collection.view'],
                                    ['Extra Classes Collection', 'reports.extra_classes_collection', $base, 'reports.extra_classes_collection.view'],
                                ];

                                if (auth()->user()?->can('reports.outflows.view')) {
                                    array_splice($links, 2, 0, [[ 'All Outflows', 'reports.outflows', $base, 'reports.outflows.view' ]]);
                                }

                                $vsExpectedParams = array_merge($base, [
                                    'from_month' => $fromMonth,
                                    'to_month' => $toMonth,
                                ]);
                            @endphp

                            @foreach($links as [$label, $route, $params, $permission])
                                @can($permission)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $label }}</td>
                                        <td class="px-4 py-3">
                                            @can('reports.download')
                                                <a class="text-red-700 hover:text-red-900 font-semibold" href="{{ route($route, array_merge($params, ['pdf' => 1])) }}">PDF</a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endcan
                                        </td>
                                        <td class="px-4 py-3">
                                            @can('reports.download')
                                                <a class="text-green-700 hover:text-green-900 font-semibold" href="{{ route($route, array_merge($params, ['download' => 1])) }}">CSV</a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endcan
                                        </td>
                                        <td class="px-4 py-3">
                                            @can('reports.download')
                                                <a class="text-indigo-700 hover:text-indigo-900 font-semibold" href="{{ route($route, array_merge($params, ['excel' => 1])) }}">XLSX</a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endcan
                                        </td>
                                    </tr>
                                @endcan
                            @endforeach

                            @can('reports.fee_collection_vs_expected.view')
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">Collected vs Expected (Monthly Fees)</td>
                                    <td class="px-4 py-3">
                                        @can('reports.download')
                                            <a class="text-red-700 hover:text-red-900 font-semibold" href="{{ route('reports.fee_collection_vs_expected', array_merge($vsExpectedParams, ['pdf' => 1])) }}">PDF</a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endcan
                                    </td>
                                    <td class="px-4 py-3">
                                        @can('reports.download')
                                            <a class="text-green-700 hover:text-green-900 font-semibold" href="{{ route('reports.fee_collection_vs_expected', array_merge($vsExpectedParams, ['download' => 1])) }}">CSV</a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endcan
                                    </td>
                                    <td class="px-4 py-3">
                                        @can('reports.download')
                                            <a class="text-indigo-700 hover:text-indigo-900 font-semibold" href="{{ route('reports.fee_collection_vs_expected', array_merge($vsExpectedParams, ['excel' => 1])) }}">XLSX</a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endcan
                                    </td>
                                </tr>
                            @endcan
                        </tbody>
                    </table>

                    <div class="mt-4 text-xs text-gray-500">
                        Tip: add <span class="font-mono">preset=today</span>, <span class="font-mono">preset=this_week</span>, or <span class="font-mono">preset=month&amp;month=2026-02</span> and click the links.
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
