<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">{{ $title }}</h2>
                <p class="text-gray-600 text-sm mt-1">Filtered by payment method and date range</p>
            </div>
            <a class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition" href="{{ route('reports.index') }}">← Back to Reports</a>
        </div>
    </x-slot>

    <div class="py-10 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow border border-gray-100 mb-6">
                <div class="border-b border-gray-200 px-6 py-5 bg-gradient-to-r from-indigo-50 to-sky-50">
                    <h3 class="text-lg font-semibold text-gray-800">Adjust Period</h3>
                    <p class="text-sm text-gray-600 mt-1">Choose a date range</p>
                </div>
                <div class="p-6">
                    <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                        <div>
                            <x-input-label for="from" :value="__('From Date')" class="font-semibold mb-2" />
                            <x-text-input id="from" name="from" type="text" placeholder="YYYY-MM-DD" class="mt-1 block w-full border-gray-300 rounded-lg" :value="$filters['from'] ?? ''" />
                        </div>
                        <div>
                            <x-input-label for="to" :value="__('To Date')" class="font-semibold mb-2" />
                            <x-text-input id="to" name="to" type="text" placeholder="YYYY-MM-DD" class="mt-1 block w-full border-gray-300 rounded-lg" :value="$filters['to'] ?? ''" />

                            @if(($account ?? null) === 'bank')
                                <label class="mt-2 flex items-center gap-2 text-sm text-gray-700 select-none">
                                    <input type="checkbox" name="include_pending_cheques" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        @checked((bool)($filters['include_pending_cheques'] ?? false))>
                                    <span>Include pending cheques</span>
                                </label>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition shadow-sm">Apply</button>
                            <a href="{{ $account === 'bank' ? route('reports.bank_transactions') : route('reports.cash_transactions') }}" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg transition shadow-sm border border-gray-300">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        Total In: <span class="font-semibold text-gray-900">Rs {{ number_format((float) $totalIn, 2) }}</span>
                        <span class="mx-2">|</span>
                        Total Out: <span class="font-semibold text-gray-900">Rs {{ number_format((float) $totalOut, 2) }}</span>
                        <span class="mx-2">|</span>
                        Net: <span class="font-semibold {{ ($totalIn - $totalOut) >= 0 ? 'text-emerald-700' : 'text-red-700' }}">Rs {{ number_format((float) ($totalIn - $totalOut), 2) }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-gray-500">
                                <th class="text-left px-6 py-3">Date</th>
                                <th class="text-left px-6 py-3">Type</th>
                                <th class="text-left px-6 py-3">Ref</th>
                                <th class="text-left px-6 py-3">Description</th>
                                <th class="text-left px-6 py-3">Method</th>
                                <th class="text-right px-6 py-3">In (Rs)</th>
                                <th class="text-right px-6 py-3">Out (Rs)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($items as $row)
                                <tr>
                                    <td class="px-6 py-3 whitespace-nowrap">{{ $row['date'] ? $row['date']->format('Y-m-d') : '—' }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap">{{ $row['type'] }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap">{{ $row['ref'] }}</td>
                                    <td class="px-6 py-3">{{ $row['description'] }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap">{{ $row['method'] }}</td>
                                    <td class="px-6 py-3 text-right font-mono">{{ $row['in'] ? number_format((float) $row['in'], 2) : '' }}</td>
                                    <td class="px-6 py-3 text-right font-mono">{{ $row['out'] ? number_format((float) $row['out'], 2) : '' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-6 py-6 text-gray-500" colspan="7">No transactions found for this range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $items->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
