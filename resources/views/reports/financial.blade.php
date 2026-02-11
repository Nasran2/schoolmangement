<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Financial Summary</h2>
                <p class="text-gray-600 text-sm mt-1">Overview of your school's financial performance</p>
            </div>
            <a class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition" href="{{ route('reports.index') }}">← Back to Reports</a>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filter Card -->
            <div class="bg-white rounded-lg shadow-lg border border-gray-100 mb-8">
                <div class="border-b border-gray-200 px-6 py-6 bg-gradient-to-r from-purple-50 to-pink-50">
                    <h3 class="text-lg font-semibold text-gray-800">Adjust Period</h3>
                    <p class="text-sm text-gray-600 mt-1">Filter financial data by custom date range</p>
                </div>
                
                <div class="p-8">
                    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-6">
                        <!-- From Date -->
                        <div>
                            <x-input-label for="from" :value="__('From Date')" class="font-semibold mb-2" />
                            <x-text-input 
                                id="from" 
                                name="from" 
                                type="text" placeholder="DD-MM-YYYY" 
                                class="mt-1 block w-full border-gray-300 focus:border-purple-500 focus:ring-purple-500 rounded-lg shadow-sm" 
                                :value="isset($filters['from']) ? $filters['from'] : ''" 
                            />
                        </div>

                        <!-- To Date -->
                        <div>
                            <x-input-label for="to" :value="__('To Date')" class="font-semibold mb-2" />
                            <x-text-input 
                                id="to" 
                                name="to" 
                                type="text" placeholder="DD-MM-YYYY" 
                                class="mt-1 block w-full border-gray-300 focus:border-purple-500 focus:ring-purple-500 rounded-lg shadow-sm" 
                                :value="isset($filters['to']) ? $filters['to'] : ''" 
                            />
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <x-input-label for="method" :value="__('Method')" class="font-semibold mb-2" />
                            @php
                                $m = $filters['method'] ?? 'all';
                                if (!$m) { $m = 'all'; }
                            @endphp
                            <select id="method" name="method" class="mt-1 block w-full border-gray-300 focus:border-purple-500 focus:ring-purple-500 rounded-lg shadow-sm rounded-lg">
                                <option value="all" @selected($m === 'all')>All</option>
                                <option value="cash" @selected($m === 'cash')>Cash</option>
                                <option value="bank" @selected($m === 'bank')>Bank (Transfer + Cheque)</option>
                                <option value="bank_transfer" @selected($m === 'bank_transfer')>Bank Transfer</option>
                                <option value="cheque" @selected($m === 'cheque')>Cheque</option>
                            </select>
                        </div>

                        <!-- Toggle: Daily vs Aggregated -->
                        <div class="sm:col-span-2 flex items-end">
                            <label class="inline-flex items-center gap-2 select-none">
                                <input type="checkbox" name="daily" value="1" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                    @checked(isset($filters['daily']) && $filters['daily'])>
                                <span class="text-sm text-gray-700">Show per-day entries</span>
                            </label>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-end gap-2 col-span-1 lg:col-span-2">
                            <button 
                                type="submit" 
                                class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition shadow-sm"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                Apply Filter
                            </button>
                            <a
                                href="{{ route('reports.financial') }}"
                                class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg transition shadow-sm border border-gray-300"
                                title="Reset Filter"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Reset
                            </a>
                            @can('reports.download')
                                <a
                                    class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition shadow-sm"
                                    href="{{ route('reports.financial', array_merge(request()->query(), ['pdf' => 1])) }}"
                                    title="Download as PDF"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M8.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.3A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H8.5z"></path>
                                        <path d="M9 13h2v5H9z"></path>
                                    </svg>
                                    PDF
                                </a>
                            @endcan
                        </div>
                        <!-- Quick Ranges -->
                        <div class="lg:col-span-6 mt-4">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('reports.financial', array_merge(request()->query(), ['from' => now()->toDateString(), 'to' => now()->toDateString()])) }}" class="px-3 py-1.5 text-sm rounded-md border border-gray-300 bg-white hover:bg-gray-50">Today</a>
                                <a href="{{ route('reports.financial', array_merge(request()->query(), ['from' => now()->copy()->subMonth()->startOfMonth()->toDateString(), 'to' => now()->toDateString()])) }}" class="px-3 py-1.5 text-sm rounded-md border border-gray-300 bg-white hover:bg-gray-50">Last 1 Month</a>
                                <a href="{{ route('reports.financial', array_merge(request()->query(), ['from' => now()->copy()->subMonths(2)->startOfMonth()->toDateString(), 'to' => now()->toDateString()])) }}" class="px-3 py-1.5 text-sm rounded-md border border-gray-300 bg-white hover:bg-gray-50">Last 2 Months</a>
                                <a href="{{ route('reports.financial', array_merge(request()->query(), ['from' => now()->copy()->subMonths(3)->startOfMonth()->toDateString(), 'to' => now()->toDateString()])) }}" class="px-3 py-1.5 text-sm rounded-md border border-gray-300 bg-white hover:bg-gray-50">Last 3 Months</a>
                                <a href="{{ route('reports.financial', array_merge(request()->query(), ['from' => now()->copy()->subMonths(6)->startOfMonth()->toDateString(), 'to' => now()->toDateString()])) }}" class="px-3 py-1.5 text-sm rounded-md border border-gray-300 bg-white hover:bg-gray-50">Last 6 Months</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @foreach($days as $d)
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 mb-8 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            @if(!empty($school['logo']))
                                <img src="{{ asset('storage/'.$school['logo']) }}" alt="Logo" class="h-12 w-12 rounded-full object-cover">
                            @endif
                            <div>
                                <div class="text-2xl font-extrabold tracking-wide" style="font-family: Georgia, 'Times New Roman', serif;">
                                    {{ strtoupper($school['name']) }}
                                </div>
                                <div class="text-sm text-gray-600">Tel: {{ $school['phone'] }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            @if(!empty($filters['daily']))
                                <div class="text-xs text-gray-500">Date</div>
                                <div class="text-lg font-semibold">{{ $d['date']->format('M d, Y') }}</div>
                            @else
                                <div class="text-xs text-gray-500">Period</div>
                                <div class="text-lg font-semibold">
                                    {{ \Carbon\Carbon::parse($filters['from'] ?? $d['date'])->format('M d, Y') }} — {{ \Carbon\Carbon::parse($filters['to'] ?? $d['date'])->format('M d, Y') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Debit (Income) Side -->
                            <div>
                                <h4 class="text-lg font-bold mb-3">Debit (Income)</h4>
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-gray-500">
                                            <th class="text-left py-2">Description</th>
                                            <th class="text-left py-2">Folio</th>
                                            <th class="text-right py-2">Amount (Rs)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($d['debits'] as $row)
                                            <tr>
                                                <td class="py-2">{{ $row['description'] }}</td>
                                                <td class="py-2">{{ $row['ref'] ?? '—' }}</td>
                                                <td class="py-2 text-right">{{ number_format($row['amount'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="bg-gray-50 font-semibold">
                                            <td class="py-2">Total Debit</td>
                                            <td></td>
                                            <td class="py-2 text-right">{{ number_format($d['opening'] + $d['income_total'], 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Credit (Expense) Side -->
                            <div>
                                <h4 class="text-lg font-bold mb-3">Credit (Expense)</h4>
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-gray-500">
                                            <th class="text-left py-2">Description</th>
                                            <th class="text-right py-2">Amount (Rs)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @forelse($d['credits'] as $row)
                                            <tr>
                                                <td class="py-2">{{ $row['description'] }}</td>
                                                <td class="py-2 text-right">{{ number_format($row['amount'], 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td class="py-2 text-gray-500">No expenses</td>
                                                <td class="py-2 text-right">0.00</td>
                                            </tr>
                                        @endforelse
                                        <tr class="bg-gray-50 font-semibold">
                                            <td class="py-2">Total Credit</td>
                                            <td class="py-2 text-right">{{ number_format($d['expense_total'], 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mt-4 text-sm text-gray-600 flex flex-wrap gap-4">
                            <div>Cash In: <span class="font-semibold text-gray-900">Rs {{ number_format((float) ($d['income_cash'] ?? 0), 2) }}</span></div>
                            <div>Bank In: <span class="font-semibold text-gray-900">Rs {{ number_format((float) ($d['income_bank'] ?? 0), 2) }}</span></div>
                            <div>Cash Out: <span class="font-semibold text-gray-900">Rs {{ number_format((float) ($d['expense_cash'] ?? 0), 2) }}</span></div>
                            <div>Bank Out: <span class="font-semibold text-gray-900">Rs {{ number_format((float) ($d['expense_bank'] ?? 0), 2) }}</span></div>
                        </div>

                        <div class="mt-6 border-t pt-4 flex items-center justify-between">
                            <div class="text-sm text-gray-600">B.B.F (Opening): <span class="font-semibold text-gray-900">Rs {{ number_format($d['opening'], 2) }}</span></div>
                            <div class="text-lg font-extrabold">Closing Balance: Rs {{ number_format($d['closing'], 2) }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
