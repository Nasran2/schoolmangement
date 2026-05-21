<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Daily Ledger</h2>
                <p class="text-gray-600 text-sm mt-1">Opening balance → revenues → expenses → closing balance</p>
            </div>
            <a class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition" href="{{ route('reports.index') }}">← Back to Reports</a>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 mb-8">
                <div class="px-6 py-6 bg-gradient-to-r from-indigo-50/80 to-sky-50/70 rounded-t-2xl">
                    <h3 class="text-lg font-semibold text-gray-800">Filter</h3>
                    <p class="text-sm text-gray-600 mt-1">Choose dates, payment method and toggle pending cheques</p>
                </div>

                <div class="px-6 py-8">
                    <form method="GET" class="grid gap-6 lg:grid-cols-4">
                        <div class="space-y-2">
                            <x-input-label for="from" :value="__('From Date')" class="font-semibold text-gray-700" />
                            <x-text-input id="from" name="from" type="text" placeholder="YYYY-MM-DD" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm" :value="$filters['from'] ?? ''" />
                        </div>

                        <div class="space-y-2">
                            <x-input-label for="to" :value="__('To Date')" class="font-semibold text-gray-700" />
                            <x-text-input id="to" name="to" type="text" placeholder="YYYY-MM-DD" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm" :value="$filters['to'] ?? ''" />
                        </div>

                        <div class="space-y-2">
                            <x-input-label for="method" :value="__('Method')" class="font-semibold text-gray-700" />
                            @php
                                $m = $filters['method'] ?? 'all';
                                if (! $m) { $m = 'all'; }
                            @endphp
                            <select id="method" name="method" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="all" @selected($m === 'all')>All</option>
                                <option value="cash" @selected($m === 'cash')>Cash</option>
                                <option value="bank" @selected($m === 'bank')>Bank (Transfer + Cheque)</option>
                                <option value="bank_transfer" @selected($m === 'bank_transfer')>Bank Transfer</option>
                                <option value="cheque" @selected($m === 'cheque')>Cheque</option>
                            </select>
                        </div>

                        <div class="flex flex-col justify-between gap-2">
                            <label class="flex items-center gap-2 text-sm text-gray-700 select-none">
                                <input type="checkbox" name="include_pending_cheques" value="1" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    @checked((bool)($filters['include_pending_cheques'] ?? false))>
                                <span class="text-sm font-medium">Include pending cheques</span>
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <button type="submit" class="flex-1 min-w-[140px] inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition shadow">Apply</button>
                                <a href="{{ route('reports.daily_ledger') }}" class="flex-1 min-w-[140px] inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold rounded-lg transition">Reset</a>
                            </div>
                        </div>
                    </form>

                    <div class="mt-6 flex flex-wrap gap-3">
                        @foreach([
                            'today' => ['from' => now()->toDateString(), 'to' => now()->toDateString(), 'label' => 'Today'],
                            '1w' => ['from' => now()->copy()->subDays(6)->toDateString(), 'to' => now()->toDateString(), 'label' => 'Last 1 Week'],
                            '2w' => ['from' => now()->copy()->subDays(13)->toDateString(), 'to' => now()->toDateString(), 'label' => 'Last 2 Weeks'],
                            '1m' => ['from' => now()->copy()->subMonth()->toDateString(), 'to' => now()->toDateString(), 'label' => 'Last 1 Month'],
                            '2m' => ['from' => now()->copy()->subMonths(2)->toDateString(), 'to' => now()->toDateString(), 'label' => 'Last 2 Months'],
                        ] as $preset)
                            <a href="{{ route('reports.daily_ledger', array_merge(request()->query(), ['from' => $preset['from'], 'to' => $preset['to']])) }}" class="px-4 py-2 text-sm rounded-full border border-gray-300 bg-white hover:bg-indigo-50 text-gray-700 transition">{{ $preset['label'] }}</a>
                        @endforeach
                        @can('reports.download')
                            <a class="px-4 py-2 text-sm rounded-full border border-transparent bg-indigo-600 text-white hover:bg-indigo-700 transition" href="{{ route('reports.daily_ledger', array_merge(request()->query(), ['pdf' => 1])) }}">PDF</a>
                            <a class="px-4 py-2 text-sm rounded-full border border-transparent bg-emerald-600 text-white hover:bg-emerald-700 transition" href="{{ route('reports.daily_ledger', array_merge(request()->query(), ['excel' => 1])) }}">Excel</a>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500">Opening Balance (As of {{ $bbfDate->format('Y-m-d') }})</div>
                        <div class="text-xl font-extrabold">Rs {{ number_format((float) $openingBalance, 2) }}</div>
                        <div class="mt-1 text-sm text-gray-600">
                            Cash: <span class="font-semibold text-gray-900">Rs {{ number_format((float) ($openingBalanceCash ?? 0), 2) }}</span>
                            <span class="mx-2">|</span>
                            Bank: <span class="font-semibold text-gray-900">Rs {{ number_format((float) ($openingBalanceBank ?? 0), 2) }}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500">Period</div>
                        <div class="text-sm font-semibold">{{ $filters['from'] ?? '' }} — {{ $filters['to'] ?? '' }}</div>
                    </div>
                </div>

                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-3">Revenues</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-gray-500">
                                    <th class="text-left px-4 py-3">Date</th>
                                    <th class="text-left px-4 py-3">Bill</th>
                                    <th class="text-left px-4 py-3">Student</th>
                                    <th class="text-left px-4 py-3">Category</th>
                                    <th class="text-left px-4 py-3">Details</th>
                                    <th class="text-left px-4 py-3">Method</th>
                                    <th class="text-left px-4 py-3">Cheque Date</th>
                                    <th class="text-right px-4 py-3">Amount (Rs)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($revenues as $r)
                                    <tr class="{{ ($r['status'] ?? null) === 'cancelled' ? 'bg-red-50 text-red-800' : '' }}">
                                        <td class="px-4 py-3 whitespace-nowrap">{{ $r['date'] ? $r['date']->format('Y-m-d') : '—' }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $r['ref'] }}
                                            @if(($r['status'] ?? null) === 'cancelled')
                                                <span class="ml-2 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-bold uppercase text-red-700">Cancelled</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">{{ $r['student'] }}</td>
                                        <td class="px-4 py-3">{{ $r['category'] }}</td>
                                        <td class="px-4 py-3">{{ $r['description'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">{{ $r['method'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">{{ $r['cheque_date'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-right font-mono">{{ number_format((float) $r['in'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-4 py-6 text-gray-500" colspan="8">No revenue records found.</td>
                                    </tr>
                                @endforelse
                                <tr class="bg-gray-50 font-semibold">
                                    <td class="px-4 py-3" colspan="7">Total Revenue</td>
                                    <td class="px-4 py-3 text-right font-mono">{{ number_format((float) $totalIn, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 mt-8 mb-3">Expenses (including refunds)</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-gray-500">
                                    <th class="text-left px-4 py-3 w-20">Date</th>
                                    <th class="text-left px-4 py-3 w-16">Type</th>
                                    <th class="text-left px-4 py-3 w-12">Ref</th>
                                    <th class="text-left px-4 py-3 w-20">Student</th>
                                    <th class="text-left px-4 py-3 w-20">Category</th>
                                    <th class="text-left px-4 py-3 flex-1">Details</th>
                                    <th class="text-left px-4 py-3 w-14">Method</th>
                                    <th class="text-left px-4 py-3 w-16">Cheque Date</th>
                                    <th class="text-right px-4 py-3 w-20">Amount (Rs)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($expenses as $e)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap w-20">{{ $e['date'] ? $e['date']->format('Y-m-d') : '—' }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap w-16 truncate">{{ $e['type'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap w-12 truncate">{{ $e['ref'] }}</td>
                                        <td class="px-4 py-3 w-20 truncate">{{ $e['student'] }}</td>
                                        <td class="px-4 py-3 w-20 truncate">{{ $e['category'] }}</td>
                                        <td class="px-4 py-3 flex-1">{{ $e['description'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap w-14 truncate">{{ $e['method'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap w-16 truncate">{{ $e['cheque_date'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-right font-mono w-20">{{ number_format((float) $e['out'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-4 py-6 text-gray-500" colspan="9">No expense records found.</td>
                                    </tr>
                                @endforelse
                                <tr class="bg-gray-50 font-semibold">
                                    <td class="px-4 py-3" colspan="8">Total Expense</td>
                                    <td class="px-4 py-3 text-right font-mono">{{ number_format((float) $totalOut, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-8 border-t pt-4 flex items-center justify-between">
                        <div class="text-sm text-gray-600">Opening: <span class="font-semibold text-gray-900">Rs {{ number_format((float) $openingBalance, 2) }}</span></div>
                        <div class="text-sm text-gray-600">Revenue: <span class="font-semibold text-gray-900">Rs {{ number_format((float) $totalIn, 2) }}</span></div>
                        <div class="text-sm text-gray-600">Expense: <span class="font-semibold text-gray-900">Rs {{ number_format((float) $totalOut, 2) }}</span></div>
                        <div class="text-right">
                            <div class="text-lg font-extrabold">Closing Balance: Rs {{ number_format((float) $closingBalance, 2) }}</div>
                            <div class="mt-1 text-sm text-gray-600 space-y-1">
                                <div>Closing Bank: <span class="font-semibold text-gray-900">Rs {{ number_format((float) ($closingBalanceBank ?? 0), 2) }}</span></div>
                                <div>Cash in Hand: <span class="font-semibold text-gray-900">Rs {{ number_format((float) ($closingBalanceCash ?? 0), 2) }}</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
