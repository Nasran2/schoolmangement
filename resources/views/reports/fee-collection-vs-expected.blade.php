<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Collected vs Expected (Monthly Fees)</h2>
                <p class="text-gray-600 text-sm mt-1">Expected monthly fees vs collected (from monthly allocations)</p>
            </div>
            <a class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition" href="{{ route('reports.index') }}">← Back to Reports</a>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-lg border border-gray-100 mb-8">
                <div class="border-b border-gray-200 px-6 py-6 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <h3 class="text-lg font-semibold text-gray-800">Filters</h3>
                </div>
                <div class="p-8">
                    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-6">
                        <div>
                            <x-input-label for="from_month" :value="__('From Month')" class="font-semibold mb-2" />
                            <x-text-input id="from_month" name="from_month" type="month" class="mt-1 block w-full" :value="$filters['from_month'] ?? ''" />
                        </div>
                        <div>
                            <x-input-label for="to_month" :value="__('To Month')" class="font-semibold mb-2" />
                            <x-text-input id="to_month" name="to_month" type="month" class="mt-1 block w-full" :value="$filters['to_month'] ?? ''" />
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 select-none">
                                <input type="checkbox" name="only_active" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked(($filters['only_active'] ?? '1') === '1')>
                                <span class="text-sm font-semibold text-gray-700">Only Active Students</span>
                            </label>
                        </div>
                        <div class="flex items-end gap-2 lg:col-span-3">
                            <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-sm">Filter</button>
                            @can('reports.download')
                                <a class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition shadow-sm" href="{{ route('reports.fee_collection_vs_expected', array_merge(request()->query(), ['pdf' => 1])) }}">PDF</a>
                                <a class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition shadow-sm" href="{{ route('reports.fee_collection_vs_expected', array_merge(request()->query(), ['download' => 1])) }}">CSV</a>
                            @endcan
                        </div>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <p class="text-sm text-gray-600 font-medium">Total Expected</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">Rs {{ number_format((float) ($totals['expected'] ?? 0), 2) }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <p class="text-sm text-gray-600 font-medium">Total Collected</p>
                    <p class="text-3xl font-bold text-indigo-600 mt-2">Rs {{ number_format((float) ($totals['collected'] ?? 0), 2) }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <p class="text-sm text-gray-600 font-medium">Total Due</p>
                    <p class="text-3xl font-bold text-red-600 mt-2">Rs {{ number_format((float) ($totals['due'] ?? 0), 2) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Expected</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Collected</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Due</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($rows as $r)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $r['label'] ?? $r['month'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">Rs {{ number_format((float) $r['expected'], 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">Rs {{ number_format((float) $r['collected'], 2) }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-red-700 text-right">Rs {{ number_format((float) $r['due'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">No records found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
