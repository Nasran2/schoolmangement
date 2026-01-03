<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Refund / Waiver</h2>
                <p class="text-gray-600 text-sm mt-1">Search by Bill No (first priority) or Student name/phone</p>
            </div>
            <a class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition" href="{{ route('revenue.items.index') }}">← Back to Revenue</a>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('status'))
                <div class="mb-6 p-4 rounded-lg bg-green-50 text-green-800 border border-green-200">{{ session('status') }}</div>
            @endif

            <div class="bg-white rounded-lg shadow-lg border border-gray-100 mb-8">
                <div class="border-b border-gray-200 px-6 py-6 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <h3 class="text-lg font-semibold text-gray-800">Search</h3>
                </div>
                <div class="p-8">
                    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-6">
                        <div class="lg:col-span-2">
                            <x-input-label for="bill_no" :value="__('Bill No (Priority)')" class="font-semibold mb-2" />
                            <x-text-input id="bill_no" name="bill_no" type="text" class="mt-1 block w-full" placeholder="Ex: B-000123" :value="$filters['bill_no'] ?? ''" />
                        </div>
                        <div class="lg:col-span-2">
                            <x-input-label for="q" :value="__('Student Name / Phone / Admission / Bill No')" class="font-semibold mb-2" />
                            <x-text-input id="q" name="q" type="text" class="mt-1 block w-full" placeholder="Ex: Nimal / 07xxxxxxx" :value="$filters['q'] ?? ''" />
                        </div>
                        <div class="flex items-end lg:col-span-2 gap-2">
                            <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-sm">Search</button>
                            <a href="{{ route('revenue.adjustments.index') }}" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold rounded-lg transition">Reset</a>
                        </div>
                    </form>

                    @if($message)
                        <div class="mt-6 p-4 rounded-lg bg-yellow-50 text-yellow-900 border border-yellow-200">{{ $message }}</div>
                    @endif
                </div>
            </div>

            @if($rows->isNotEmpty())
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bill</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Refunded</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net Collected</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Waiver</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($rows as $row)
                                    @php $r = $row['revenue']; @endphp
                                    <tr>
                                        <td class="px-6 py-4 text-sm text-gray-900 font-semibold">{{ $r->bill_no }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700">{{ optional($r->paid_at)->format('Y-m-d') }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700">{{ $r->student?->name ?? '-' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700">{{ $r->category?->name ?? '-' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ number_format((float)$r->amount, 2) }}</td>
                                        <td class="px-6 py-4 text-sm text-red-700 text-right">{{ number_format((float)$row['refunds'], 2) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900 text-right font-semibold">{{ number_format((float)$row['net_collected'], 2) }}</td>
                                        <td class="px-6 py-4 text-sm text-indigo-700 text-right">{{ number_format((float)$row['waivers'], 2) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700">
                                            <div class="grid grid-cols-1 gap-2">
                                                <form method="POST" action="{{ route('revenue.items.refund', $r) }}" class="flex gap-2">
                                                    @csrf
                                                    <input name="amount" type="number" step="0.01" min="0.01" class="w-24 border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-lg shadow-sm" placeholder="Refund" required>
                                                    <input name="reason" type="text" class="flex-1 border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-lg shadow-sm" placeholder="Reason (optional)">
                                                    <button class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold">Save</button>
                                                </form>

                                                <form method="POST" action="{{ route('revenue.items.waiver', $r) }}" class="flex gap-2">
                                                    @csrf
                                                    <input name="amount" type="number" step="0.01" min="0.01" class="w-24 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm" placeholder="Waiver" required>
                                                    <input name="reason" type="text" class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm" placeholder="Reason (optional)">
                                                    <button class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold">Save</button>
                                                </form>

                                                @php $adjs = $row['adjustments'] ?? collect(); @endphp
                                                @if($adjs->isNotEmpty())
                                                    <div class="mt-2 rounded-lg border border-gray-200 bg-gray-50 p-3">
                                                        <div class="text-xs font-semibold text-gray-700 mb-2">History</div>
                                                        <div class="space-y-2">
                                                            @foreach($adjs as $a)
                                                                <div class="flex items-start justify-between gap-3 text-sm">
                                                                    <div>
                                                                        <div class="text-gray-800">
                                                                            <span class="font-semibold {{ $a->type === 'refund' ? 'text-red-700' : 'text-indigo-700' }}">{{ ucfirst($a->type) }}</span>
                                                                            <span class="ml-2">Rs {{ number_format((float) $a->amount, 2) }}</span>
                                                                        </div>
                                                                        <div class="text-xs text-gray-600">
                                                                            {{ optional($a->created_at)->format('Y-m-d H:i') }}
                                                                            @if(!empty($a->reason)) • {{ $a->reason }} @endif
                                                                            @if($a->creator?->name) • {{ $a->creator->name }} @endif
                                                                        </div>
                                                                    </div>
                                                                    <div class="shrink-0">
                                                                        @if($a->type === 'refund')
                                                                            <a href="{{ route('printer.refund', $a) }}" class="text-xs font-semibold text-indigo-700 hover:text-indigo-900">Print Slip</a>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
