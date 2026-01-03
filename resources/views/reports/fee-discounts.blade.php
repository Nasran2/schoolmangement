<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Discount / Waiver Report</h2>
                <p class="text-gray-600 text-sm mt-1">All waiver adjustments with filters and exports.</p>
            </div>
            <div class="flex gap-2">
                <a class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 rounded-lg font-medium transition" href="{{ route('reports.index') }}">Back to Reports</a>
                <a class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold" href="{{ request()->fullUrlWithQuery(['pdf' => 1]) }}">Download PDF</a>
                <a class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 rounded-lg font-medium" href="{{ request()->fullUrlWithQuery(['download' => 1]) }}">Download CSV</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="GET" class="bg-white rounded-lg shadow border border-gray-100 p-6 grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase">From</label>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase">To</label>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase">Category</label>
                    <select name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" @selected(($filters['category_id'] ?? '') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase">Class</label>
                    <select name="class_room_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All</option>
                        @foreach($classRooms as $cr)
                            <option value="{{ $cr->id }}" @selected(($filters['class_room_id'] ?? '') == $cr->id)>{{ $cr->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase">Search</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Bill / Student / Admission / Phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>
                <div class="md:col-span-5 flex justify-end">
                    <button class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold">Filter</button>
                </div>
            </form>

            <div class="bg-white rounded-lg shadow border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-sm text-gray-600">Total Waivers: <span class="font-semibold text-gray-900">Rs {{ number_format((float) $totalAmount, 2) }}</span></div>
                </div>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Bill</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Category</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Reason</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($items as $a)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ optional($a->created_at)->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $a->revenue?->bill_no }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $a->student?->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $a->student?->classRoom?->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $a->revenue?->category?->name }}</td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold text-indigo-700">{{ number_format((float) $a->amount, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $a->reason ?: '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $a->creator?->name ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-6 text-center text-sm text-gray-600" colspan="8">No waivers found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $items->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
