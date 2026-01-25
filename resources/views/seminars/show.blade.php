<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Seminar: {{ $seminar->name }}</h1>
                <div class="flex items-center gap-2 mt-1 text-sm text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>{{ $seminar->date?->format('F d, Y') }}</span>
                    @if($seminar->start_time)
                        <span>&bull;</span>
                        <span>{{ \Carbon\Carbon::parse($seminar->start_time)->format('h:i A') }} {{ $seminar->end_time ? ' - '.\Carbon\Carbon::parse($seminar->end_time)->format('h:i A') : '' }}</span>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3">
                 <a href="{{ route('seminars.edit', $seminar) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50 hover:text-indigo-600 transition-all text-sm font-medium shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Details
                </a>
                <a href="{{ route('seminars.payments', $seminar) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-all text-sm font-semibold shadow-md shadow-indigo-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Manage Payments
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto space-y-6">
        
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">Total Enrolled</div>
                    <div class="text-2xl font-bold text-gray-900 mt-1">{{ $seminar->students()->count() }}</div>
                </div>
                <div class="h-12 w-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">Expected Revenue</div>
                    <div class="text-2xl font-bold text-gray-900 mt-1">Rs {{ number_format($seminar->students()->sum('amount'), 2) }}</div>
                </div>
                <div class="h-12 w-12 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">Collection Rate</div>
                    @php
                        $total = $seminar->students()->count();
                        $paid = $seminar->students()->where('paid', true)->count();
                        $rate = $total > 0 ? round(($paid / $total) * 100) : 0;
                    @endphp
                    <div class="flex items-baseline gap-2 mt-1">
                        <div class="text-2xl font-bold text-gray-900">{{ $rate }}%</div>
                        <div class="text-sm font-medium text-gray-500">({{ $paid }}/{{ $total }})</div>
                    </div>
                </div>
                <!-- Progress ring or simple pie chart svg -->
                 <div class="h-12 w-12 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center relative">
                    <svg class="h-12 w-12 transform -rotate-90" viewBox="0 0 36 36">
                        <path class="text-indigo-100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3" />
                        <path class="text-indigo-600" stroke-dasharray="{{ $rate }}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center text-[10px] font-bold">{{ $rate }}%</div>
                </div>
            </div>
        </div>

        <section class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">Teacher payouts</h2>
                    <p class="text-sm text-gray-500">Log payouts as expenses so the visiting teacher balance stays transparent.</p>
                </div>
                <div class="text-xs text-gray-500">
                    Target: {{ number_format($teacherTarget, 2) }} • Paid: {{ number_format($teacherPaidTotal, 2) }} • Due: {{ number_format($teacherDueTotal, 2) }}
                </div>
            </div>

            <div class="px-6 py-4 border-b border-gray-100 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                    <div class="text-xs text-gray-500 uppercase">Target payout</div>
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($teacherTarget, 2) }}</div>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                    <div class="text-xs text-gray-500 uppercase">Paid so far</div>
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($teacherPaidTotal, 2) }}</div>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                    <div class="text-xs text-gray-500 uppercase">Remaining due</div>
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($teacherDueTotal, 2) }}</div>
                </div>
            </div>

            <div class="px-6 py-4 border-b border-gray-100">
                <form action="{{ route('seminars.teacher-payments.store', $seminar) }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-700">Amount ({{ number_format($teacherDueTotal, 2) }} remaining)</label>
                        <input type="number" step="0.01" min="0.01" max="{{ $teacherDueTotal }}" name="amount" value="{{ old('amount') }}" class="mt-1 block w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" {{ $teacherDueTotal <= 0 ? 'disabled' : '' }}>
                        @error('amount')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700">Paid at</label>
                        <input type="date" name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d')) }}" class="mt-1 block w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @error('paid_at')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700">Notes</label>
                        <input type="text" name="notes" value="{{ old('notes') }}" class="mt-1 block w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Payment reference">
                        @error('notes')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full inline-flex justify-center rounded-lg bg-indigo-600 text-white text-sm font-semibold px-4 py-2 transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" {{ $teacherDueTotal <= 0 ? 'disabled' : '' }}>
                            Record payment
                        </button>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Notes</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($teacherPayments as $payment)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $payment->paid_at?->format('d M, Y') ?? $payment->created_at->format('d M, Y') }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ number_format($payment->amount, 2) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $payment->notes ?: '—' }}</td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <form action="{{ route('seminars.teacher-payments.destroy', [$seminar, $payment]) }}" method="POST" onsubmit="return confirm('Delete this payment?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 text-xs font-semibold">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">
                                    No payments recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <h2 class="text-base font-semibold text-gray-800">Enrolled Students</h2>
                <!-- Filter placeholder -->
                <div class="relative">
                    <input type="text" placeholder="Search students..." class="pl-9 pr-4 py-2 text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 w-full md:w-64">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Attendance</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Fee</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Date Paid</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($students as $row)
                            <tr class="hover:bg-gray-50/80 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-9 w-9 bg-gradient-to-br from-indigo-100 to-white border border-indigo-50 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm shadow-sm">
                                            {{ substr($row->student?->name ?? '?', 0, 1) }}
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $row->student?->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $row->student?->admission_number }}</div>
                                        </div>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($row->present)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Present
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Absent
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($row->paid)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Paid
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-100">
                                            Unpaid
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-700 font-mono">
                                    {{ number_format($row->amount ?? $seminar->fee_per_student, 2) }}
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                    {{ $row->paid_at ? $row->paid_at->format('d M Y') : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                 {{ $students->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
