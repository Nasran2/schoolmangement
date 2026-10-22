<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="font-bold text-2xl text-gray-900">
                        {{ ($account ?? '') === 'cash' ? 'Cash in Hand & Transactions' : $title }}
                    </h2>
                    @if(($account ?? '') === 'cash')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            Cash in Hand
                        </span>
                    @endif
                </div>
                <p class="text-gray-600 text-sm mt-1">
                    {{ ($account ?? '') === 'cash' ? 'Live cash tracking, full historical ledger, and filtered running balances' : 'Filtered by payment method and date range' }}
                </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                @can('reports.download')
                    <a href="{{ request()->fullUrlWithQuery(['pdf' => 1]) }}" class="inline-flex items-center px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export PDF
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['download' => 1]) }}" class="inline-flex items-center px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Export Excel / CSV
                    </a>
                @endcan
                <a class="inline-flex items-center px-3.5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-xs font-semibold rounded-lg transition" href="{{ route('reports.index') }}">
                    ← Reports
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(($account ?? '') === 'cash')
                <!-- Cash in Hand KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Live Cash in Hand -->
                    <div class="bg-gradient-to-br from-emerald-600 to-teal-700 text-white p-5 rounded-xl shadow-md flex flex-col justify-between border border-emerald-500">
                        <div class="flex items-center justify-between text-emerald-100 text-xs font-medium uppercase tracking-wider">
                            <span>Cash in Hand (Today)</span>
                            <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-300 animate-pulse"></span>
                        </div>
                        <div class="mt-2">
                            <span class="text-2xl font-black tracking-tight font-mono">
                                Rs {{ number_format((float) ($liveCashToday ?? 0), 2) }}
                            </span>
                        </div>
                        <p class="text-[11px] text-emerald-100/90 mt-1">Current live cash available</p>
                    </div>

                    <!-- Period Opening Balance -->
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200 flex flex-col justify-between">
                        <span class="text-gray-500 text-xs font-medium uppercase tracking-wider">Period Opening</span>
                        <div class="mt-2">
                            <span class="text-xl font-bold text-gray-900 font-mono">
                                Rs {{ number_format((float) ($openingBalance ?? 0), 2) }}
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">
                            {{ !empty($filters['is_all_time']) ? 'Initial Base Balance' : 'As of ' . ($filters['from'] ?? 'start') }}
                        </p>
                    </div>

                    <!-- Total Cash In -->
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200 flex flex-col justify-between">
                        <span class="text-emerald-700 text-xs font-medium uppercase tracking-wider flex items-center gap-1">
                            <span>+ Cash Received</span>
                        </span>
                        <div class="mt-2">
                            <span class="text-xl font-bold text-emerald-600 font-mono">
                                Rs {{ number_format((float) ($totalIn ?? 0), 2) }}
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Revenues & collections</p>
                    </div>

                    <!-- Total Cash Out -->
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200 flex flex-col justify-between">
                        <span class="text-rose-700 text-xs font-medium uppercase tracking-wider flex items-center gap-1">
                            <span>- Cash Spent / Out</span>
                        </span>
                        <div class="mt-2">
                            <span class="text-xl font-bold text-rose-600 font-mono">
                                Rs {{ number_format((float) ($totalOut ?? 0), 2) }}
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Expenses, transfers, salaries</p>
                    </div>

                    <!-- Period Closing Balance -->
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200 flex flex-col justify-between">
                        <span class="text-gray-700 text-xs font-medium uppercase tracking-wider">Period Closing</span>
                        <div class="mt-2">
                            <span class="text-xl font-bold font-mono {{ ($closingBalance ?? 0) >= 0 ? 'text-indigo-600' : 'text-red-600' }}">
                                Rs {{ number_format((float) ($closingBalance ?? 0), 2) }}
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">
                            {{ !empty($filters['is_all_time']) ? 'Whole-time balance' : 'At end of period' }}
                        </p>
                    </div>
                </div>
            @endif

            <!-- Filter & Preset Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50/80 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        <h3 class="text-sm font-semibold text-gray-800">Quick Period Presets & Filter</h3>
                    </div>

                    @if(($account ?? '') === 'cash')
                        <!-- 1-Click Quick Presets -->
                        <div class="flex flex-wrap items-center gap-1.5 text-xs">
                            <a href="{{ route('reports.cash_transactions', ['period' => 'all']) }}"
                               class="px-3 py-1.5 rounded-lg font-medium transition {{ ($filters['period'] ?? '') === 'all' || !empty($filters['is_all_time']) ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-100' }}">
                                ★ Whole Time (All Time)
                            </a>
                            <a href="{{ route('reports.cash_transactions', ['period' => 'today']) }}"
                               class="px-3 py-1.5 rounded-lg font-medium transition {{ ($filters['period'] ?? '') === 'today' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-100' }}">
                                Today
                            </a>
                            <a href="{{ route('reports.cash_transactions', ['period' => 'this_week']) }}"
                               class="px-3 py-1.5 rounded-lg font-medium transition {{ ($filters['period'] ?? '') === 'this_week' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-100' }}">
                                This Week
                            </a>
                            <a href="{{ route('reports.cash_transactions', ['period' => 'this_month']) }}"
                               class="px-3 py-1.5 rounded-lg font-medium transition {{ ($filters['period'] ?? '') === 'this_month' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-100' }}">
                                This Month
                            </a>
                            <a href="{{ route('reports.cash_transactions', ['period' => 'this_year']) }}"
                               class="px-3 py-1.5 rounded-lg font-medium transition {{ ($filters['period'] ?? '') === 'this_year' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-100' }}">
                                This Year
                            </a>
                        </div>
                    @endif
                </div>

                <div class="p-6">
                    <form method="GET" action="{{ ($account ?? '') === 'bank' ? route('reports.bank_transactions') : route('reports.cash_transactions') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                        <div>
                            <x-input-label for="from" :value="__('From Date')" class="text-xs font-semibold text-gray-700 mb-1" />
                            <x-text-input id="from" name="from" type="date" class="block w-full border-gray-300 rounded-lg text-sm" :value="$filters['from'] ?? ''" />
                        </div>
                        <div>
                            <x-input-label for="to" :value="__('To Date')" class="text-xs font-semibold text-gray-700 mb-1" />
                            <x-text-input id="to" name="to" type="date" class="block w-full border-gray-300 rounded-lg text-sm" :value="$filters['to'] ?? ''" />
                        </div>

                        @if(($account ?? '') === 'cash')
                            <div>
                                <x-input-label for="type" :value="__('Transaction Type')" class="text-xs font-semibold text-gray-700 mb-1" />
                                <select id="type" name="type" class="block w-full border-gray-300 rounded-lg text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="all" @selected(($filters['type'] ?? 'all') === 'all')>All Cash Types</option>
                                    <option value="revenue" @selected(($filters['type'] ?? '') === 'revenue')>Income / Revenues</option>
                                    <option value="expense" @selected(($filters['type'] ?? '') === 'expense')>Expenses</option>
                                    <option value="transfer" @selected(($filters['type'] ?? '') === 'transfer')>Owner Transfers</option>
                                    <option value="salary" @selected(($filters['type'] ?? '') === 'salary')>Teacher Salaries</option>
                                    <option value="refund" @selected(($filters['type'] ?? '') === 'refund')>Refunds</option>
                                </select>
                            </div>

                            <div>
                                <x-input-label for="search" :value="__('Search (Ref / Note)')" class="text-xs font-semibold text-gray-700 mb-1" />
                                <x-text-input id="search" name="search" type="text" placeholder="Bill #, student, note..." class="block w-full border-gray-300 rounded-lg text-sm" :value="$filters['search'] ?? ''" />
                            </div>
                        @else
                            <div class="flex items-center pt-6">
                                <label class="flex items-center gap-2 text-sm text-gray-700 select-none">
                                    <input type="checkbox" name="include_pending_cheques" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        @checked((bool)($filters['include_pending_cheques'] ?? false))>
                                    <span>Include pending cheques</span>
                                </label>
                            </div>
                            <div></div>
                        @endif

                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                                Apply
                            </button>
                            <a href="{{ ($account ?? '') === 'bank' ? route('reports.bank_transactions') : route('reports.cash_transactions', ['period' => 'all']) }}"
                               class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg border border-gray-300 transition">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Transactions Table Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4 bg-gray-50/50">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-gray-800">
                            Showing {{ $items->total() }} transaction(s)
                        </span>
                        @if(!empty($filters['is_all_time']))
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 border border-indigo-200">
                                Whole Time (All Time)
                            </span>
                        @elseif(!empty($filters['from']) || !empty($filters['to']))
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700">
                                {{ $filters['from'] ?? 'Start' }} to {{ $filters['to'] ?? 'End' }}
                            </span>
                        @endif
                    </div>

                    <div class="text-xs text-gray-600 flex items-center gap-4 flex-wrap font-mono">
                        <span>Total In: <strong class="text-emerald-700">Rs {{ number_format((float) $totalIn, 2) }}</strong></span>
                        <span>Total Out: <strong class="text-rose-700">Rs {{ number_format((float) $totalOut, 2) }}</strong></span>
                        <span>Net: <strong class="{{ ($totalIn - $totalOut) >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">Rs {{ number_format((float) ($totalIn - $totalOut), 2) }}</strong></span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-gray-600 font-semibold text-xs uppercase tracking-wider">
                            <tr>
                                <th class="text-left px-5 py-3">Date</th>
                                <th class="text-left px-4 py-3">Type</th>
                                <th class="text-left px-4 py-3">Ref</th>
                                <th class="text-left px-5 py-3">Description</th>
                                <th class="text-right px-4 py-3 text-emerald-700">In (Rs)</th>
                                <th class="text-right px-4 py-3 text-rose-700">Out (Rs)</th>
                                @if(($account ?? '') === 'cash')
                                    <th class="text-right px-5 py-3 text-indigo-900 bg-indigo-50/50">Cash in Hand (Rs)</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($items as $row)
                                @php
                                    $rawType = $row['raw_type'] ?? strtolower($row['type'] ?? '');
                                    $typeBadge = match ($rawType) {
                                        'revenue', 'income' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'expense' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        'transfer' => 'bg-sky-50 text-sky-700 border-sky-200',
                                        'salary' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'refund' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        default => 'bg-gray-50 text-gray-700 border-gray-200',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="px-5 py-3 whitespace-nowrap text-xs text-gray-600 font-mono">
                                        {{ $row['date'] ? $row['date']->format('Y-m-d') : '—' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border {{ $typeBadge }}">
                                            {{ $row['type'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap font-mono text-xs font-medium text-gray-900">
                                        {{ $row['ref'] }}
                                    </td>
                                    <td class="px-5 py-3 text-gray-700 max-w-md truncate" title="{{ $row['description'] }}">
                                        {{ $row['description'] }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-xs font-semibold text-emerald-600">
                                        {{ !empty($row['in']) && (float)$row['in'] > 0 ? number_format((float) $row['in'], 2) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-xs font-semibold text-rose-600">
                                        {{ !empty($row['out']) && (float)$row['out'] > 0 ? number_format((float) $row['out'], 2) : '—' }}
                                    </td>
                                    @if(($account ?? '') === 'cash')
                                        <td class="px-5 py-3 text-right font-mono text-xs font-bold bg-indigo-50/30 {{ ($row['balance'] ?? 0) >= 0 ? 'text-indigo-950' : 'text-red-700' }}">
                                            Rs {{ number_format((float) ($row['balance'] ?? 0), 2) }}
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-6 py-12 text-center text-gray-500" colspan="{{ ($account ?? '') === 'cash' ? 7 : 6 }}">
                                        <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        No cash transactions found for the selected filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $items->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
