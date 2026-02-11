<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">EPF/ETF Totals</h2>
                <p class="text-gray-600 text-sm mt-1">Combined totals for Teacher EPF + Company EPF/ETF</p>
            </div>
            <a class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition" href="{{ route('reports.index') }}">← Back to Reports</a>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-lg border border-gray-100 mb-8">
                <div class="border-b border-gray-200 px-6 py-6 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <h3 class="text-lg font-semibold text-gray-800">Filters</h3>
                    <p class="text-sm text-gray-600 mt-1">Filter by date range, month, teacher, and status</p>
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

                        <div class="flex items-end lg:col-span-2">
                            <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-sm">Filter</button>
                        </div>

                        @can('reports.download')
                            <div class="flex items-end lg:col-span-2">
                                <a class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition shadow-sm"
                                   href="{{ route('reports.epf_etf_totals', array_merge(request()->query(), ['pdf' => 1])) }}">PDF</a>
                            </div>
                        @endcan
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <p class="text-sm text-gray-600 font-medium">Teacher EPF</p>
                    <p class="text-3xl font-bold text-indigo-600 mt-2">Rs {{ number_format((float)($totals['employee_epf'] ?? 0), 2) }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <p class="text-sm text-gray-600 font-medium">Company EPF</p>
                    <p class="text-3xl font-bold text-blue-600 mt-2">Rs {{ number_format((float)($totals['employer_epf'] ?? 0), 2) }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <p class="text-sm text-gray-600 font-medium">Company ETF</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">Rs {{ number_format((float)($totals['employer_etf'] ?? 0), 2) }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <p class="text-sm text-gray-600 font-medium">Grand Total</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">Rs {{ number_format((float)($totals['grand_total'] ?? 0), 2) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg border border-gray-100 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Teacher Totals</h3>
                    <p class="text-sm text-gray-600">Teacher EPF + Company EPF + Company ETF totals per teacher</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teacher</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Payments</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Teacher EPF</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Company EPF</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Company ETF</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($teacherTotals ?? [] as $row)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $row['teacher']?->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ (int) ($row['payments'] ?? 0) }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-indigo-700 text-right">Rs {{ number_format((float)($row['employee_epf'] ?? 0), 2) }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-blue-700 text-right">Rs {{ number_format((float)($row['employer_epf'] ?? 0), 2) }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-green-700 text-right">Rs {{ number_format((float)($row['employer_etf'] ?? 0), 2) }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900 text-right">Rs {{ number_format((float)($row['total'] ?? 0), 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">No teachers found for selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if(($groupByMonth ?? false) && !empty($monthTotals ?? []))
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 overflow-hidden mb-8">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Month Totals</h3>
                        <p class="text-sm text-gray-600">Grouped totals by month</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Payments</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Teacher EPF</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Company EPF</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Company ETF</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($monthTotals as $m)
                                    @php
                                        $total = (float)($m['employee_epf'] ?? 0) + (float)($m['employer_epf'] ?? 0) + (float)($m['employer_etf'] ?? 0);
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $m['label'] ?? ($m['month'] ?? '') }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ (int) ($m['payments'] ?? 0) }}</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-indigo-700 text-right">Rs {{ number_format((float)($m['employee_epf'] ?? 0), 2) }}</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-blue-700 text-right">Rs {{ number_format((float)($m['employer_epf'] ?? 0), 2) }}</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-green-700 text-right">Rs {{ number_format((float)($m['employer_etf'] ?? 0), 2) }}</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900 text-right">Rs {{ number_format($total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                <p class="text-sm text-gray-600">Note: Company EPF uses stored amounts. Older payments (before the update) may show 0 until recalculated.</p>
            </div>
        </div>
    </div>
</x-app-layout>
