<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">{{ $title }}</h2>
                <p class="text-gray-600 text-sm mt-1">Cheque entries (income + expense) with cheque date + passed date</p>
            </div>
            <a class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition" href="{{ route('reports.index') }}">← Back to Reports</a>
        </div>
    </x-slot>

    <div class="py-10 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow border border-gray-100 mb-6">
                <div class="border-b border-gray-200 px-6 py-5 bg-gradient-to-r from-indigo-50 to-sky-50">
                    <h3 class="text-lg font-semibold text-gray-800">Filters</h3>
                    <p class="text-sm text-gray-600 mt-1">Adjust range and cheque status</p>
                </div>
                <div class="p-6">
                    <form method="GET" class="grid grid-cols-1 sm:grid-cols-5 gap-4 items-end">
                        <div>
                            <x-input-label for="from" :value="__('From Date')" class="font-semibold mb-2" />
                            <x-text-input id="from" name="from" type="text" placeholder="YYYY-MM-DD" class="mt-1 block w-full border-gray-300 rounded-lg" :value="$filters['from'] ?? ''" />
                        </div>
                        <div>
                            <x-input-label for="to" :value="__('To Date')" class="font-semibold mb-2" />
                            <x-text-input id="to" name="to" type="text" placeholder="YYYY-MM-DD" class="mt-1 block w-full border-gray-300 rounded-lg" :value="$filters['to'] ?? ''" />
                        </div>
                        <div>
                            <x-input-label for="type" :value="__('Type')" class="font-semibold mb-2" />
                            <select id="type" name="type" class="mt-1 block w-full border-gray-300 rounded-lg">
                                <option value="all" @selected(($filters['type'] ?? 'all') === 'all')>All</option>
                                <option value="income" @selected(($filters['type'] ?? 'all') === 'income')>Income</option>
                                <option value="expense" @selected(($filters['type'] ?? 'all') === 'expense')>Expense</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="status" :value="__('Status')" class="font-semibold mb-2" />
                            <select id="status" name="status" class="mt-1 block w-full border-gray-300 rounded-lg">
                                <option value="passed" @selected(($filters['status'] ?? 'all') === 'passed')>Passed</option>
                                <option value="pending" @selected(($filters['status'] ?? 'all') === 'pending')>Pending</option>
                                <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>All</option>
                            </select>

                            <label class="mt-2 flex items-center gap-2 text-sm text-gray-700 select-none">
                                <input type="checkbox" name="include_pending_cheques" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    @checked((bool)($filters['include_pending_cheques'] ?? true))>
                                <span>Include pending cheques</span>
                            </label>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition shadow-sm">Apply</button>
                            <a href="{{ route('reports.cheque_history') }}" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg transition shadow-sm border border-gray-300">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-gray-500">
                                <th class="text-left px-6 py-3">Cheque Date</th>
                                <th class="text-left px-6 py-3">Passed Date</th>
                                <th class="text-left px-6 py-3">Direction</th>
                                <th class="text-left px-6 py-3">Ref</th>
                                <th class="text-left px-6 py-3">Party</th>
                                <th class="text-left px-6 py-3">Description</th>
                                <th class="text-left px-6 py-3">Cheque No</th>
                                <th class="text-left px-6 py-3">Bank</th>
                                <th class="text-left px-6 py-3">Status</th>
                                <th class="text-right px-6 py-3">In (Rs)</th>
                                <th class="text-right px-6 py-3">Out (Rs)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($items as $row)
                                <tr>
                                    <td class="px-6 py-3 whitespace-nowrap">{{ $row['cheque_date'] ?: '—' }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap">{{ $row['passed_date'] ?: '—' }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $row['direction'] === 'In' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $row['direction'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap">{{ $row['ref'] }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap">{{ $row['party'] }}</td>
                                    <td class="px-6 py-3">{{ $row['description'] }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap">{{ $row['cheque_no'] ?: '—' }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap">{{ $row['bank'] ?: '—' }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap">{{ $row['status'] }}</td>
                                    <td class="px-6 py-3 text-right font-mono">{{ $row['in'] ? number_format((float) $row['in'], 2) : '' }}</td>
                                    <td class="px-6 py-3 text-right font-mono">{{ $row['out'] ? number_format((float) $row['out'], 2) : '' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-6 py-6 text-gray-500" colspan="12">No cheques found for this range.</td>
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
