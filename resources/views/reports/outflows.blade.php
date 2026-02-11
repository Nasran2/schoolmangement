<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">All Outflows</h2>
                <p class="text-gray-600 text-sm mt-1">Expenses + salary payments + seminar/extra class teacher payouts</p>
            </div>
            <a class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition" href="{{ route('reports.index') }}">← Back to Reports</a>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-lg border border-gray-100 mb-8">
                <div class="border-b border-gray-200 px-6 py-6 bg-gradient-to-r from-indigo-50 to-blue-50">
                    <h3 class="text-lg font-semibold text-gray-800">Filter Outflows</h3>
                    <p class="text-sm text-gray-600 mt-1">Refine by date range and payment method</p>
                </div>

                <div class="p-8">
                    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                        <div>
                            <x-input-label for="from" :value="__('From Date')" class="font-semibold mb-2" />
                            <x-text-input id="from" name="from" type="text" placeholder="YYYY-MM-DD" class="mt-1 block w-full rounded-lg" :value="($filters['from'] ?? '')" />
                        </div>

                        <div>
                            <x-input-label for="to" :value="__('To Date')" class="font-semibold mb-2" />
                            <x-text-input id="to" name="to" type="text" placeholder="YYYY-MM-DD" class="mt-1 block w-full rounded-lg" :value="($filters['to'] ?? '')" />
                        </div>

                        <div>
                            <x-input-label for="method" :value="__('Method')" class="font-semibold mb-2" />
                            <select id="method" name="method" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm">
                                @php $m = (string)($filters['method'] ?? 'all'); @endphp
                                <option value="all" @selected($m==='all')>All</option>
                                <option value="cash" @selected($m==='cash')>Cash</option>
                                <option value="bank" @selected($m==='bank')>Bank (Transfer + Cheque)</option>
                                <option value="bank_transfer" @selected($m==='bank_transfer')>Bank Transfer</option>
                                <option value="cheque" @selected($m==='cheque')>Cheque</option>
                            </select>
                        </div>

                        <div class="flex items-end gap-2 col-span-1 lg:col-span-2">
                            <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition shadow-sm">Filter</button>

                            @can('reports.download')
                                <a class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition shadow-sm" href="{{ route('reports.outflows', array_merge(request()->query(), ['pdf' => 1])) }}">PDF</a>
                                <a class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition shadow-sm" href="{{ route('reports.outflows', array_merge(request()->query(), ['download' => 1])) }}">CSV</a>
                                <a class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition shadow-sm" href="{{ route('reports.outflows', array_merge(request()->query(), ['excel' => 1])) }}">Excel</a>
                            @endcan
                        </div>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <p class="text-sm text-gray-600 font-medium">Total Rows</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ (int)($summary['total_count'] ?? 0) }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <p class="text-sm text-gray-600 font-medium">Total Outflow</p>
                    <p class="text-3xl font-bold text-red-600 mt-2">Rs {{ number_format((float)($summary['total_amount'] ?? 0), 2) }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <p class="text-sm text-gray-600 font-medium">Breakdown</p>
                    <div class="mt-3 space-y-1 text-sm text-gray-700">
                        @foreach(($summary['by_type'] ?? collect()) as $t => $amt)
                            <div class="flex items-center justify-between">
                                <span class="truncate">{{ $t }}</span>
                                <span class="font-semibold">Rs {{ number_format((float)$amt, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg border border-gray-100 overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-6 bg-gradient-to-r from-indigo-50 to-blue-50">
                    <h3 class="text-lg font-semibold text-gray-800">Outflow Transactions</h3>
                    <p class="text-sm text-gray-600 mt-1">Merged view without double-counting linked payout expenses</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Date</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Type</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Category</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Party</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Method</th>
                                <th class="px-6 py-3 text-right font-semibold text-gray-700">Amount</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $r)
                                <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                                    <td class="px-6 py-3 text-gray-600">{{ $r['date'] ?? '—' }}</td>
                                    <td class="px-6 py-3">
                                        <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-800">{{ $r['type'] ?? '—' }}</span>
                                    </td>
                                    <td class="px-6 py-3 text-gray-700">{{ $r['category'] ?? '—' }}</td>
                                    <td class="px-6 py-3 text-gray-700">{{ $r['party'] ?? '—' }}</td>
                                    <td class="px-6 py-3 text-gray-700">{{ $r['method'] ?? '—' }}</td>
                                    <td class="px-6 py-3 text-right font-semibold text-gray-900">Rs {{ number_format((float)($r['amount'] ?? 0), 2) }}</td>
                                    <td class="px-6 py-3 text-gray-600 text-xs">{{ $r['notes'] ?? '' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-10 text-center text-gray-500">No outflow records found for this range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
