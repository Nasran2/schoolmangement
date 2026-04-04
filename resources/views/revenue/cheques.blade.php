<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Cheques</h2>
                <div class="mt-1 text-sm text-gray-600">All revenue items paid by cheque</div>
            </div>
            <div class="flex gap-4">
                @can('revenue.manage')
                    <a href="{{ route('revenue.items.index') }}" class="text-sm text-gray-600 hover:underline">All Revenue</a>
                @endcan
                @can('revenue.add')
                    <a href="{{ route('revenue.items.create') }}" class="text-sm text-indigo-600 hover:underline">Add Revenue</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-10 bg-gradient-to-b from-slate-50 to-white" x-data="{ passModal: false, passAction: '', passMode: 'today', passDate: '{{ now()->toDateString() }}', today: '{{ now()->toDateString() }}' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-xl border border-green-100 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-sm">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-sm">
                    <div class="font-semibold">Action failed</div>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Filters</p>
                        <h3 class="text-lg font-semibold text-gray-900">Search and filter cheques</h3>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('revenue.cheques.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Reset</a>
                        <button form="cheque-filters" type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-700">Apply Filters</button>
                    </div>
                </div>

                <form id="cheque-filters" method="GET" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-5">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Search</label>
                        <input name="q" type="text" placeholder="Bill / Student / Cheque No / Bank" value="{{ $filters['q'] ?? '' }}" class="mt-2 block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Status</label>
                        <select name="status" class="mt-2 block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            <option value="hold" @selected(in_array(($filters['status'] ?? ''), ['hold','pending'], true))>On Hold</option>
                            <option value="confirmed" @selected(($filters['status'] ?? '') === 'confirmed')>Passed (Confirmed)</option>
                            <option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>Returned (Rejected)</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">State</label>
                        <select name="state" class="mt-2 block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            <option value="upcoming" @selected(($filters['state'] ?? '') === 'upcoming')>Upcoming (future date)</option>
                            <option value="due" @selected(($filters['state'] ?? '') === 'due')>Due today</option>
                            <option value="overdue" @selected(($filters['state'] ?? '') === 'overdue')>Overdue</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Cheque Date From</label>
                        <input name="cheque_from" type="text" data-datepicker placeholder="YYYY-MM-DD" value="{{ $filters['cheque_from'] ?? '' }}" class="mt-2 block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Cheque Date To</label>
                        <input name="cheque_to" type="text" data-datepicker placeholder="YYYY-MM-DD" value="{{ $filters['cheque_to'] ?? '' }}" class="mt-2 block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </form>
            </div>

            <div class="bg-white border border-gray-100 shadow-sm rounded-2xl">
                <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Cheque Payments</p>
                        <h3 class="text-lg font-semibold text-gray-900">Cheques</h3>
                    </div>
                    <div class="text-sm text-gray-500">Showing {{ $items->total() }} records</div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <th class="px-4 py-3 text-left">Bill</th>
                                <th class="px-4 py-3 text-left">Student</th>
                                <th class="px-4 py-3 text-left">Cheque Date</th>
                                <th class="px-4 py-3 text-left">Time Left</th>
                                <th class="px-4 py-3 text-left">Cheque Info</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm text-gray-800">
                            @forelse ($items as $item)
                                @php
                                    $chequeDate = $item->cheque_date ? \Carbon\Carbon::parse($item->cheque_date)->startOfDay() : null;
                                    $today = \Carbon\Carbon::today();
                                    $daysLeft = $chequeDate ? $today->diffInDays($chequeDate, false) : null;
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $item->bill_no ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        @if ($item->student)
                                            <a class="text-indigo-600 hover:underline" href="{{ route('students.show', $item->student) }}">{{ $item->student->name }}</a>
                                            <div class="text-[11px] text-gray-500">ID: {{ $item->student->admission_number ?? $item->student->id }}</div>
                                        @else
                                            <div class="text-gray-900">{{ data_get($item->payment_meta, 'student_name') ?? '—' }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">{{ $item->cheque_date ? \Carbon\Carbon::parse($item->cheque_date)->format('d M Y') : '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if($daysLeft === null)
                                            —
                                        @elseif($daysLeft > 0)
                                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ $daysLeft }} day{{ $daysLeft > 1 ? 's' : '' }} left</span>
                                        @elseif($daysLeft === 0)
                                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Due today</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">Overdue {{ abs($daysLeft) }} day{{ abs($daysLeft) > 1 ? 's' : '' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-xs text-gray-700">No: <span class="font-semibold">{{ data_get($item->payment_meta, 'cheque_number') ?? '—' }}</span></div>
                                        <div class="text-xs text-gray-700">Bank: <span class="font-semibold">{{ data_get($item->payment_meta, 'bank') ?? '—' }}</span></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @php($st = $item->payment_status ?? 'confirmed')
                                        @if(in_array($st, ['hold', 'pending'], true))
                                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">On Hold</span>
                                        @elseif($st === 'confirmed')
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Passed</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">Returned</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ number_format($item->amount, 2) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            @can('revenue.manage')
                                                @if(in_array((string)($item->payment_status ?? 'hold'), ['hold', 'pending'], true))
                                                    <button type="button"
                                                        class="inline-flex items-center rounded-md bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow hover:bg-emerald-700"
                                                        @click="passAction='{{ route('revenue.items.cheque.passed', $item) }}'; passMode='today'; passDate=today; passModal=true">
                                                        Passed
                                                    </button>
                                                    <form method="POST" action="{{ route('revenue.items.cheque.returned', $item) }}" onsubmit="return confirm('Mark this cheque as RETURNED? It will be rejected and will NOT count as paid.');">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center rounded-md bg-rose-600 px-3 py-2 text-xs font-semibold text-white shadow hover:bg-rose-700">Returned</button>
                                                    </form>
                                                @endif
                                                <a class="inline-flex items-center rounded-md bg-gray-900 px-3 py-2 text-xs font-semibold text-white shadow hover:bg-gray-700" href="{{ route('revenue.items.edit', $item) }}">Open</a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-6 text-center text-sm text-gray-600" colspan="8">No cheque records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-100">{{ $items->links() }}</div>
            </div>

            <div x-show="passModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/40" @click="passModal=false"></div>
                <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl">
                    <div class="px-6 py-4 border-b">
                        <div class="text-base font-semibold text-gray-900">Mark Cheque as Passed</div>
                        <div class="text-sm text-gray-600 mt-1">Choose the passed date (today or custom)</div>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm text-gray-800">
                                <input type="radio" class="text-indigo-600" value="today" x-model="passMode">
                                <span>Today (<span class="font-semibold" x-text="today"></span>)</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm text-gray-800">
                                <input type="radio" class="text-indigo-600" value="custom" x-model="passMode">
                                <span>Custom date</span>
                            </label>
                        </div>

                        <div>
                            <x-input-label for="passed_date2" :value="__('Passed Date')" class="font-semibold mb-2" />
                            <input id="passed_date2" type="date" class="block w-full border-gray-300 rounded-lg"
                                x-model="passDate" :disabled="passMode !== 'custom'">
                            <p class="text-xs text-gray-500 mt-1">You can select or type the date.</p>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t flex items-center justify-end gap-2">
                        <button type="button" class="inline-flex items-center rounded-md bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200" @click="passModal=false">Cancel</button>
                        <form method="POST" :action="passAction">
                            @csrf
                            <input type="hidden" name="passed_date" :value="passMode === 'today' ? today : passDate">
                            <button type="submit" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-700">Confirm Passed</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
