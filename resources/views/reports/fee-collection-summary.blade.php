<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Fee Collection Summary</h2>
                <p class="text-gray-600 text-sm mt-1">Totals grouped by day or month (from revenue records)</p>
            </div>
            <a class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition" href="{{ route('reports.index') }}">← Back to Reports</a>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-lg border border-gray-100 mb-8">
                <div class="border-b border-gray-200 px-6 py-6 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <h3 class="text-lg font-semibold text-gray-800">Filters</h3>
                    <p class="text-sm text-gray-600 mt-1">Filter by date range and category</p>
                </div>

                <div class="p-8">
                    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-6">
                        <div>
                            <x-input-label for="from" :value="__('From Date')" class="font-semibold mb-2" />
                            <x-text-input id="from" name="from" type="date" class="mt-1 block w-full" :value="$filters['from'] ?? ''" />
                        </div>
                        <div>
                            <x-input-label for="to" :value="__('To Date')" class="font-semibold mb-2" />
                            <x-text-input id="to" name="to" type="date" class="mt-1 block w-full" :value="$filters['to'] ?? ''" />
                        </div>
                        <div>
                            <x-input-label for="category_id" :value="__('Category')" class="font-semibold mb-2" />
                            <select id="category_id" name="category_id" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm">
                                <option value="">All</option>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}" @selected(($filters['category_id'] ?? '') == $c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="group" :value="__('Group By')" class="font-semibold mb-2" />
                            <select id="group" name="group" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm">
                                <option value="day" @selected(($filters['group'] ?? 'day') === 'day')>Day</option>
                                <option value="month" @selected(($filters['group'] ?? '') === 'month')>Month</option>
                            </select>
                        </div>

                        <div class="flex items-end gap-2 lg:col-span-2">
                            <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-sm">Filter</button>
                            @can('reports.download')
                                <a class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition shadow-sm" href="{{ route('reports.fee_collection_summary', array_merge(request()->query(), ['pdf' => 1])) }}">PDF</a>
                                <a class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition shadow-sm" href="{{ route('reports.fee_collection_summary', array_merge(request()->query(), ['download' => 1])) }}">CSV</a>
                            @endcan
                        </div>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <p class="text-sm text-gray-600 font-medium">Payments</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $totalPayments }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <p class="text-sm text-gray-600 font-medium">Total Collected</p>
                    <p class="text-3xl font-bold text-indigo-600 mt-2">Rs {{ number_format((float) $totalAmount, 2) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ ($filters['group'] ?? 'day') === 'month' ? 'Month' : 'Day' }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Payments</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($rows as $r)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $r->grp }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ (int) $r->payments }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-indigo-700 text-right">Rs {{ number_format((float) $r->total_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-6 py-10 text-center text-sm text-gray-500">No records found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
