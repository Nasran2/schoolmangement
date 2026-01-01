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
                    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                        <!-- From Date -->
                        <div>
                            <x-input-label for="from" :value="__('From Date')" class="font-semibold mb-2" />
                            <x-text-input 
                                id="from" 
                                name="from" 
                                type="date" 
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
                                type="date" 
                                class="mt-1 block w-full border-gray-300 focus:border-purple-500 focus:ring-purple-500 rounded-lg shadow-sm" 
                                :value="isset($filters['to']) ? $filters['to'] : ''" 
                            />
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-end gap-2 col-span-1 lg:col-span-3">
                            <button 
                                type="submit" 
                                class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition shadow-sm"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                Apply Filter
                            </button>
                            
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
                    </form>
                </div>
            </div>

            <!-- Main Financial Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Revenue Card -->
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 overflow-hidden">
                    <div class="h-1 bg-gradient-to-r from-blue-500 to-blue-600"></div>
                    <div class="p-8">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                                <p class="text-4xl font-bold text-blue-600 mt-3">Rs {{ number_format($totalRevenue, 2) }}</p>
                                <p class="text-xs text-gray-500 mt-2">Income from all sources</p>
                            </div>
                            <div class="h-16 w-16 rounded-lg bg-blue-100 flex items-center justify-center">
                                <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expense Card -->
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 overflow-hidden">
                    <div class="h-1 bg-gradient-to-r from-red-500 to-red-600"></div>
                    <div class="p-8">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Expenses</p>
                                <p class="text-4xl font-bold text-red-600 mt-3">Rs {{ number_format($totalExpense, 2) }}</p>
                                <p class="text-xs text-gray-500 mt-2">Outflows and payables</p>
                            </div>
                            <div class="h-16 w-16 rounded-lg bg-red-100 flex items-center justify-center">
                                <svg class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Net Profit Card -->
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 overflow-hidden">
                    <div class="h-1 bg-gradient-to-r from-green-500 to-green-600"></div>
                    <div class="p-8">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Net Profit/Loss</p>
                                <p class="text-4xl font-bold {{ $netProfit >= 0 ? 'text-green-600' : 'text-red-600' }} mt-3">
                                    Rs {{ number_format($netProfit, 2) }}
                                </p>
                                <p class="text-xs text-gray-500 mt-2">{{ $netProfit >= 0 ? 'Positive balance' : 'Negative balance' }}</p>
                            </div>
                            <div class="h-16 w-16 rounded-lg {{ $netProfit >= 0 ? 'bg-green-100' : 'bg-red-100' }} flex items-center justify-center">
                                <svg class="w-8 h-8 {{ $netProfit >= 0 ? 'text-green-600' : 'text-red-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-8">
                    <div class="flex items-center mb-6">
                        <svg class="w-6 h-6 text-purple-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h.01a1 1 0 110 2H12zm-2 2a1 1 0 100-2 1 1 0 000 2zm4 2a1 1 0 110-2h.01a1 1 0 110 2H14zm3.5-6a2.5 2.5 0 00-5 0v.006L9 6a.5.5 0 00.5.5h.5v3H8a2 2 0 100 4h8a2 2 0 100-4h-1.5V6.5h.5A.5.5 0 0016 6l.5-.994v-.006z" clip-rule="evenodd"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-800">Financial Ratio</h3>
                    </div>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Revenue to Expense Ratio</span>
                            <span class="font-bold text-xl text-purple-600">
                                @if($totalExpense > 0)
                                    {{ number_format($totalRevenue / $totalExpense, 2) }}:1
                                @else
                                    ∞ (No expenses)
                                @endif
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-3 rounded-full" style="width: {{ min(($totalRevenue / max($totalRevenue, $totalExpense)) * 100, 100) }}%"></div>
                        </div>
                        <p class="text-sm text-gray-500">Shows relationship between income and spending</p>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-8">
                    <div class="flex items-center mb-6">
                        <svg class="w-6 h-6 text-indigo-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-800">Budget Allocation</h3>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-600">Expense Percentage</span>
                                <span class="font-bold text-xl text-indigo-600">
                                    @if($totalRevenue > 0)
                                        {{ number_format(($totalExpense / $totalRevenue) * 100, 1) }}%
                                    @else
                                        0%
                                    @endif
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-gradient-to-r from-red-500 to-orange-600 h-3 rounded-full" style="width: {{ min(($totalExpense / max($totalRevenue, 1)) * 100, 100) }}%"></div>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500">Percentage of revenue spent on expenses</p>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-6">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-blue-900">Financial Overview</p>
                        <p class="text-sm text-blue-700 mt-1">
                            @if ((isset($filters['from']) && $filters['from']) || (isset($filters['to']) && $filters['to']))
                                This summary displays financial data for the selected period. Use date filters to analyze specific timeframes.
                            @else
                                This summary shows your overall financial performance. Use date filters to analyze specific periods.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
