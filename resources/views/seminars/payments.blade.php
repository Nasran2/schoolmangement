<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Seminar Payments</h1>
                <div class="flex items-center gap-2 mt-1 text-sm text-gray-500">
                    <span class="font-medium text-gray-700">{{ $seminar->name }}</span>
                    <span>&bull;</span>
                    <span>{{ $seminar->date?->format('d M Y') }}</span>
                    <span>&bull;</span>
                    <span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded text-xs font-semibold">Fee: {{ number_format($seminar->fee_per_student, 2) }}</span>
                </div>
            </div>
            <a href="{{ route('seminars.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50 hover:text-gray-900 transition-all text-sm font-medium shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back
            </a>
        </div>
    </x-slot>

    <form action="{{ route('seminars.payments.update', $seminar) }}" method="POST" class="py-6 max-w-7xl mx-auto">
        @csrf
        
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <!-- Header/Filters Area -->
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <h2 class="text-base font-semibold text-gray-800">Student List</h2>

                <div class="text-xs text-gray-500">
                    Set payment method per student (default: Cash).
                </div>
                
                <!-- Quick stats could go here if available -->
                 <div class="flex items-center gap-3 text-sm text-gray-500">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Valid
                    </div>
                     <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-gray-300"></span> Unset
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Attendance</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Payment Status</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Method</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($enrollments as $index => $row)
                            @php
                                $rowPm = old("items.$index.payment_method", (string) ($row->revenue?->payment_method ?? 'cash'));
                                if (!$rowPm) { $rowPm = 'cash'; }
                                $rowBank = old("items.$index.bank_name", (string) data_get($row->revenue?->payment_meta, 'bank', ''));
                                $rowRef = old("items.$index.bank_ref_no", (string) data_get($row->revenue?->payment_meta, 'ref_no', ''));
                                $rowChequeDate = old("items.$index.cheque_date", (string) ($row->revenue?->cheque_date?->format('Y-m-d') ?? ''));
                                $rowChequeNo = old("items.$index.cheque_number", (string) data_get($row->revenue?->payment_meta, 'cheque_number', ''));
                                $rowChequeBank = old("items.$index.cheque_bank", (string) data_get($row->revenue?->payment_meta, 'bank', ''));
                            @endphp
                            <tr class="hover:bg-gray-50/80 transition-colors group">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-9 w-9 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm">
                                            {{ substr($row->student?->name ?? '?', 0, 1) }}
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $row->student?->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $row->student?->admission_number }}</div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $row->id }}">
                                </td>
                                
                                <!-- Present Toggle -->
                                <td class="px-6 py-4 whitespace-nowrap text-center" x-data="{ on: {{ $row->present ? 'true' : 'false' }} }">
                                    <input type="hidden" name="items[{{ $index }}][present]" :value="on ? '1' : '0'">
                                    <button type="button" 
                                            @click="on = !on"
                                            :class="on ? 'bg-emerald-500' : 'bg-gray-200'"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                        <span class="sr-only">Toggle Attendance</span>
                                        <span aria-hidden="true" 
                                              :class="on ? 'translate-x-5' : 'translate-x-0'"
                                              class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                    </button>
                                    <div class="mt-1 text-[10px] font-medium uppercase tracking-wide" :class="on ? 'text-emerald-600' : 'text-gray-400'" x-text="on ? 'Present' : 'Absent'"></div>
                                </td>

                                <!-- Paid Toggle -->
                                <td class="px-6 py-4 whitespace-nowrap text-center" x-data="{ paid: {{ $row->paid ? 'true' : 'false' }} }">
                                    <input type="hidden" name="items[{{ $index }}][paid]" :value="paid ? '1' : '0'">
                                    <button type="button" 
                                            @click="paid = !paid"
                                            :class="paid ? 'bg-indigo-600' : 'bg-gray-200'"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                                        <span class="sr-only">Toggle Payment</span>
                                        <span aria-hidden="true" 
                                              :class="paid ? 'translate-x-5' : 'translate-x-0'"
                                              class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                    </button>
                                     <div class="mt-1 text-[10px] font-medium uppercase tracking-wide" :class="paid ? 'text-indigo-600' : 'text-gray-400'" x-text="paid ? 'Paid' : 'Unpaid'"></div>
                                </td>

                                <!-- Method Radios (per student) -->
                                <td class="px-6 py-4 whitespace-nowrap text-center" x-data="{ pm: '{{ $rowPm }}' }">
                                    <div class="inline-flex items-center gap-3 text-xs">
                                        <label class="inline-flex items-center gap-1 cursor-pointer select-none">
                                            <input type="radio" class="h-3 w-3" name="items[{{ $index }}][payment_method]" value="cash" x-model="pm">
                                            <span class="text-gray-700">Cash</span>
                                        </label>
                                        <label class="inline-flex items-center gap-1 cursor-pointer select-none">
                                            <input type="radio" class="h-3 w-3" name="items[{{ $index }}][payment_method]" value="bank_transfer" x-model="pm">
                                            <span class="text-gray-700">Transfer</span>
                                        </label>
                                        <label class="inline-flex items-center gap-1 cursor-pointer select-none">
                                            <input type="radio" class="h-3 w-3" name="items[{{ $index }}][payment_method]" value="cheque" x-model="pm">
                                            <span class="text-gray-700">Cheque</span>
                                        </label>
                                    </div>

                                    <div x-show="pm === 'bank_transfer'" x-cloak class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <input type="text" name="items[{{ $index }}][bank_name]" value="{{ $rowBank }}" class="w-full rounded-md border-gray-300 text-xs" placeholder="Bank">
                                        <input type="text" name="items[{{ $index }}][bank_ref_no]" value="{{ $rowRef }}" class="w-full rounded-md border-gray-300 text-xs" placeholder="Ref no">
                                    </div>
                                    <div x-show="pm === 'cheque'" x-cloak class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-2">
                                        <input type="date" name="items[{{ $index }}][cheque_date]" value="{{ $rowChequeDate }}" class="w-full rounded-md border-gray-300 text-xs">
                                        <input type="text" name="items[{{ $index }}][cheque_number]" value="{{ $rowChequeNo }}" class="w-full rounded-md border-gray-300 text-xs" placeholder="Cheque no">
                                        <input type="text" name="items[{{ $index }}][cheque_bank]" value="{{ $rowChequeBank }}" class="w-full rounded-md border-gray-300 text-xs" placeholder="Bank">
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-700 font-medium font-mono">
                                    {{ number_format($row->amount ?? $seminar->fee_per_student, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Showing {{ $enrollments->firstItem() ?? 0 }} to {{ $enrollments->lastItem() ?? 0 }} of {{ $enrollments->total() }} students
                </div>
                <div class="flex items-center gap-4">
                     {{ $enrollments->links() }} 
                     <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all transform active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>
