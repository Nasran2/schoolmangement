<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Revenue</h2>
            <div class="flex gap-4">
                @can('revenue.categories.manage')
                    <a href="{{ route('revenue.categories.index') }}" class="text-sm text-gray-600 hover:underline">Categories</a>
                @endcan
                @can('revenue.add')
                    <a href="{{ route('revenue.items.create') }}" class="text-sm text-indigo-600 hover:underline">Add Revenue</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-10 bg-gradient-to-b from-slate-50 to-white">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-green-100 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-sm">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Quick filters</p>
                        <h3 class="text-lg font-semibold text-gray-900">Find receipts fast</h3>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('revenue.items.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Reset</a>
                        <button form="revenue-filters" type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-700">Apply Filters</button>
                    </div>
                </div>

                <form id="revenue-filters" method="GET" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-6" x-data="{ range: '{{ $range ?? 'all' }}' }">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Range</label>
                        <select name="range" class="mt-2 block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" x-model="range" @change="if(range !== 'custom') $refs.filterFormBtn.click()">
                            <option value="all">All Time</option>
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="this_week">This Week</option>
                            <option value="last_week">Last Week</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="custom">Custom Date</option>
                        </select>
                        <button x-ref="filterFormBtn" type="submit" class="hidden"></button>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Category</label>
                        <select id="category_id" name="category_id" class="mt-2 block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Search</label>
                        <input id="q" name="q" type="text" placeholder="Bill / Student / Category" value="{{ $filters['q'] ?? '' }}" class="mt-2 block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Per Page</label>
                        <select name="per_page" class="mt-2 block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach([15, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected(($perPage ?? 15) === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="range === 'custom'" x-cloak>
                        <label class="text-sm font-medium text-gray-700">From</label>
                        <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="mt-2 block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div x-show="range === 'custom'" x-cloak>
                        <label class="text-sm font-medium text-gray-700">To</label>
                        <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="mt-2 block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="flex items-end">
                        <div class="flex w-full items-center gap-3 rounded-xl border border-dashed border-gray-200 px-4 py-3 text-sm text-gray-600">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                                </svg>
                            </span>
                            <div class="flex-1">
                                <div class="font-semibold text-gray-900">New payment?</div>
                                @can('revenue.add')
                                    <a href="{{ route('revenue.items.create') }}" class="text-indigo-600 hover:text-indigo-700">Add Revenue</a>
                                @endcan
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="bg-white border border-gray-100 shadow-sm rounded-2xl">
                <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Revenue Records</p>
                        <h3 class="text-lg font-semibold text-gray-900">Receipts</h3>
                    </div>
                    <div class="text-sm text-gray-500">Showing {{ $items->total() }} records</div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <th class="px-4 py-3 text-left">Bill</th>
                                <th class="px-4 py-3 text-left">Date</th>
                                <th class="px-4 py-3 text-left">Category</th>
                                <th class="px-4 py-3 text-left">Student</th>
                                <th class="px-4 py-3 text-left">Method</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm text-gray-800">
                            @forelse ($items as $item)
                                @php($isCancelled = method_exists($item, 'isCancelled') ? $item->isCancelled() : (($item->payment_status ?? null) === 'cancelled'))
                                <tr class="{{ $isCancelled ? 'bg-red-50/60 text-red-900 hover:bg-red-50' : 'hover:bg-slate-50' }}">
                                    <td class="px-4 py-3 font-medium {{ $isCancelled ? 'text-red-700' : 'text-gray-900' }}">
                                        {{ $item->bill_no ?? '-' }}
                                        @if($isCancelled)
                                            <span class="ml-2 inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-red-700">Cancelled</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">{{ optional($item->paid_at)->format('d-m-Y') }}</td>
                                    <td class="px-4 py-3">{{ $item->category?->name }}</td>
                                    <td class="px-4 py-3">
                                        @if ($item->student)
                                            <a class="text-indigo-600 hover:underline" href="{{ route('students.show', $item->student) }}">{{ $item->student->name }}</a>
                                        @else
                                            –
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm {{ $isCancelled ? 'text-red-700' : 'text-gray-600' }} capitalize">{{ $isCancelled ? 'Cancelled' : ($item->payment_method ?? '-') }}</td>
                                    <td class="px-4 py-3 text-right font-semibold {{ $isCancelled ? 'text-red-700' : 'text-gray-900' }}">{{ number_format((float) $item->amount, 2) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="inline-flex items-center gap-3 text-gray-500">
                                            @can('revenue.manage')
                                                <a href="{{ route('revenue.items.receipt', $item) }}" class="hover:text-indigo-600" title="Print / View Receipt">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                                                    </svg>
                                                </a>
                                                @unless($isCancelled)
                                                    <a href="{{ route('revenue.items.edit', $item) }}" class="hover:text-indigo-600" title="Edit">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A1.25 1.25 0 0116.75 20H5.25A1.25 1.25 0 014 18.75V7.25A1.25 1.25 0 015.25 6H10" />
                                                        </svg>
                                                    </a>
                                                @endunless
                                            @endcan
                                            @can('revenue.delete')
                                                @unless($isCancelled)
                                                <span x-data="{ open:false }" class="relative inline-block">
                                                    <button type="button" class="hover:text-red-600" x-on:click="open=true" title="Cancel Bill">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                    <form x-ref="delForm" method="POST" action="{{ route('revenue.items.destroy', $item) }}" class="hidden">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="cancel_reason" x-ref="cancelReasonInput">
                                                    </form>
                                                    <template x-teleport="body">
                                                        <div x-cloak x-show="open">
                                                            <div class="fixed inset-0 bg-black/40 z-[100]" x-on:click="open=false"></div>
                                                            <div class="fixed inset-0 z-[101] flex items-center justify-center p-4">
                                                                <div class="w-[90%] max-w-sm rounded-xl bg-white p-5 shadow-xl">
                                                                    <div class="text-sm font-semibold text-gray-900">Cancel Revenue Bill</div>
                                                                    <div class="mt-2 text-sm text-gray-600">Bill {{ $item->bill_no ?? '-' }} will stay in the list, but all payment value and fee allocations will be cancelled.</div>
                                                                    <label class="mt-4 block text-sm font-medium text-gray-700" for="cancel-reason-{{ $item->id }}">Reason for cancellation</label>
                                                                    <textarea id="cancel-reason-{{ $item->id }}" x-ref="cancelReason" rows="3" required class="mt-2 block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm shadow-sm focus:border-red-500 focus:ring-red-500" placeholder="Enter reason"></textarea>
                                                                    <div class="mt-4 flex justify-end gap-2">
                                                                        <button type="button" class="rounded-lg border px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50" x-on:click="open=false">Cancel</button>
                                                                        <button type="button" class="rounded-lg bg-red-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-red-700" x-on:click="
                                                                            if (!$refs.cancelReason.value.trim()) { $refs.cancelReason.focus(); return; }
                                                                            $refs.cancelReasonInput.value = $refs.cancelReason.value.trim();
                                                                            $refs.delForm.submit();
                                                                        ">Continue</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </span>
                                                @endunless
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-6 text-center text-sm text-gray-600" colspan="7">No revenue records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-100">{{ $items->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
