<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Revenue Report</h2>
                <p class="text-gray-600 text-sm mt-1">Track all revenue transactions and payments</p>
            </div>
            <a class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition" href="{{ route('reports.index') }}">← Back to Reports</a>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filters Card -->
            <div class="bg-white rounded-lg shadow-lg border border-gray-100 mb-8">
                <div class="border-b border-gray-200 px-6 py-6 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <h3 class="text-lg font-semibold text-gray-800">Filter Revenue</h3>
                    <p class="text-sm text-gray-600 mt-1">Refine your report by date range and category</p>
                </div>
                
                <div class="p-8">
                    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                        <!-- From Date -->
                        <div>
                            <x-input-label for="from" :value="__('From Date')" class="font-semibold mb-2" />
                            <x-text-input 
                                id="from" 
                                name="from" 
                                type="text" placeholder="DD-MM-YYYY" 
                                class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm" 
                                :value="$filters['from'] ?? ''" 
                            />
                        </div>

                        <!-- To Date -->
                        <div>
                            <x-input-label for="to" :value="__('To Date')" class="font-semibold mb-2" />
                            <x-text-input 
                                id="to" 
                                name="to" 
                                type="text" placeholder="DD-MM-YYYY" 
                                class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm" 
                                :value="$filters['to'] ?? ''" 
                            />
                        </div>

                        <!-- Category -->
                        <div>
                            <x-input-label for="category_id" :value="__('Category')" class="font-semibold mb-2" />
                            <select 
                                id="category_id" 
                                name="category_id" 
                                class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm"
                            >
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected(($filters['category_id'] ?? '') == $cat->id)>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-end gap-2 col-span-1 lg:col-span-2">
                            <button 
                                type="submit" 
                                class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-sm"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                Filter
                            </button>
                            
                            @can('reports.download')
                                <a
                                    class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition shadow-sm"
                                    href="{{ route('reports.revenue', array_merge(request()->query(), ['pdf' => 1])) }}"
                                    title="Download as PDF"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M8.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.3A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H8.5z"></path>
                                        <path d="M9 13h2v5H9z"></path>
                                    </svg>
                                    PDF
                                </a>
                                
                                <a
                                    class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition shadow-sm"
                                    href="{{ route('reports.revenue', array_merge(request()->query(), ['download' => 1])) }}"
                                    title="Download as CSV"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                    CSV
                                </a>
                            @endcan
                        </div>
                    </form>
                </div>
            </div>

            <!-- Report Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 font-medium">Total Transactions</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $items->total() }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 6H6.28l-.31-1.243A1 1 0 005 4H3a1 1 0 000 2h1.692L5 7v8a2 2 0 11-4 0V7a1 1 0 012 0v8h1V7a1 1 0 000-2H3z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 font-medium">Total Revenue</p>
                            <p class="text-3xl font-bold text-green-600 mt-2">Rs {{ number_format($items->sum('amount'), 2) }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-lg bg-green-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 font-medium">Average Amount</p>
                            <p class="text-3xl font-bold text-indigo-600 mt-2">Rs {{ number_format($items->avg('amount') ?? 0, 2) }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-lg bg-indigo-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue Table -->
            <div class="bg-white rounded-lg shadow-lg border border-gray-100 overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-6 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <h3 class="text-lg font-semibold text-gray-800">Revenue Transactions</h3>
                    <p class="text-sm text-gray-600 mt-1">Complete list of all revenue entries</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Bill No</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Date</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Category</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Student</th>
                                <th class="px-6 py-3 text-right font-semibold text-gray-700">Amount</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                                <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                                    <td class="px-6 py-3 font-medium text-gray-900">{{ $item->bill_no }}</td>
                                    <td class="px-6 py-3 text-gray-600">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ optional($item->paid_at)->format('d-m-Y') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-gray-600">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ $item->category?->name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-gray-600">{{ $item->student?->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-3 text-right font-semibold text-gray-900">Rs {{ number_format($item->amount, 2) }}</td>
                                    <td class="px-6 py-3 text-gray-600 text-xs">{{ $item->notes ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <p class="font-medium">No revenue records found</p>
                                        <p class="text-sm">Try adjusting your filters or date range</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="border-t border-gray-200 px-6 py-4 bg-gray-50">
                    {{ $items->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
