<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Company ETF Report</h2>
                <p class="text-gray-600 text-sm mt-1">Company ETF contribution from teacher salary payments</p>
            </div>
            <a class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition" href="{{ route('reports.index') }}">← Back to Reports</a>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-lg border border-gray-100 mb-8">
                <div class="border-b border-gray-200 px-6 py-6 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <h3 class="text-lg font-semibold text-gray-800">Filter Company ETF</h3>
                    <p class="text-sm text-gray-600 mt-1">Filter by date range, month, and teacher</p>
                </div>

                <div class="p-8">
                    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-6">
                        <div>
                            <x-input-label for="from" :value="__('From Date')" class="font-semibold mb-2" />
                            <x-text-input id="from" name="from" type="text" placeholder="DD-MM-YYYY" class="mt-1 block w-full" :value="$filters['from'] ?? ''" />
                        </div>
                        <div>
                            <x-input-label for="to" :value="__('To Date')" class="font-semibold mb-2" />
                            <x-text-input id="to" name="to" type="text" placeholder="DD-MM-YYYY" class="mt-1 block w-full" :value="$filters['to'] ?? ''" />
                        </div>
                        <div>
                            <x-input-label for="payment_month" :value="__('Payment Month')" class="font-semibold mb-2" />
                            <x-text-input id="payment_month" name="payment_month" type="month" class="mt-1 block w-full" :value="$filters['payment_month'] ?? ''" />
                        </div>
                        <div>
                            <x-input-label for="teacher_id" :value="__('Teacher')" class="font-semibold mb-2" />
                            <select id="teacher_id" name="teacher_id" data-searchable-select class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm">
                                <option value="">All Teachers</option>
                                @foreach($teachers as $t)
                                    <option value="{{ $t->id }}" @selected(($filters['teacher_id'] ?? '') == $t->id)>{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="teacher_status" :value="__('Teacher Status')" class="font-semibold mb-2" />
                            <select id="teacher_status" name="teacher_status" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm">
                                <option value="">All</option>
                                <option value="1" @selected(($filters['teacher_status'] ?? '') === '1')>Active</option>
                                <option value="0" @selected(($filters['teacher_status'] ?? '') === '0')>Inactive</option>
                            </select>
                        </div>

                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 select-none">
                                <input type="checkbox" name="group_by_month" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    @checked(($filters['group_by_month'] ?? '') === '1')>
                                <span class="text-sm font-semibold text-gray-700">Group by Month</span>
                            </label>
                        </div>

                        <div class="flex items-end gap-2 lg:col-span-2">
                            <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-sm">Filter</button>

                            @can('reports.download')
                                <a class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition shadow-sm"
                                   href="{{ route($routeName ?? 'reports.teacher_etf', array_merge(request()->query(), ['pdf' => 1])) }}">PDF</a>
                                <a class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition shadow-sm"
                                   href="{{ route($routeName ?? 'reports.teacher_etf', array_merge(request()->query(), ['download' => 1])) }}">CSV</a>
                            @endcan
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg border border-gray-100 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Company ETF Summary</h3>
                    <p class="text-sm text-gray-600">Totals per teacher for current filters</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teacher</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Payments</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total ETF</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($teacherSummary as $row)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $row['teacher']?->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ (int) $row['payments'] }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-indigo-700 text-right">Rs {{ number_format((float) $row['total'], 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-right">
                                        <a class="inline-flex items-center px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md"
                                           href="{{ route('reports.teacher_etf', array_merge(request()->query(), ['teacher_id' => $row['teacher']?->id])) }}">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No teachers found for selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if(($groupByMonth ?? false) && !empty($monthTotals))
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 overflow-hidden mb-8">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">ETF Month Totals</h3>
                        <p class="text-sm text-gray-600">Totals grouped by salary month</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Payments</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total ETF</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($monthTotals as $m)
                                    <tr>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $m['label'] ?? ($m['month'] ?? '') }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ (int) ($m['payments'] ?? 0) }}</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-indigo-700 text-right">Rs {{ number_format((float) ($m['total'] ?? 0), 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <p class="text-sm text-gray-600 font-medium">Payments</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $items->total() }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <p class="text-sm text-gray-600 font-medium">Total ETF</p>
                    <p class="text-3xl font-bold text-indigo-600 mt-2">Rs {{ number_format($totalAmount, 2) }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <p class="text-sm text-gray-600 font-medium">Average ETF</p>
                    <p class="text-3xl font-bold text-blue-600 mt-2">Rs {{ number_format(($items->total() ? ($totalAmount / max(1,$items->total())) : 0), 2) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paid At</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teacher</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Basic Salary</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">ETF</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($items as $row)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $row->receipt_number }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ optional($row->paid_at)->format('d-m-Y') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $row->payment_month ? \Carbon\Carbon::parse($row->payment_month . '-01')->format('M Y') : 'N/A' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $row->teacher?->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">Rs {{ number_format((float) $row->base_salary, 2) }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-indigo-700 text-right">Rs {{ number_format((float) ($row->deduction_amount ?? 0), 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">No records found for selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-6">{{ $items->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
