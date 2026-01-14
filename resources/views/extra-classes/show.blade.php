<x-app-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-800">{{ $extraClass->name }}</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 capitalize">
                        {{ $extraClass->payment_type }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $extraClass->date?->format('F d, Y') }} • {{ $extraClass->start_time }} - {{ $extraClass->end_time }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('extra-classes.edit', $extraClass) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition-all">
                    Edit Class
                </a>
                <a href="{{ route('extra-classes.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-900 shadow-sm transition-all">
                    &larr; Back to List
                </a>
            </div>
        </div>

        @php
            // Calculate stats for the dashboard cards
            $allEnrollments = $extraClass->students()->get(); 
            $totalEnrollments = $allEnrollments->count();
            
            if ($extraClass->payment_type === 'daily') {
                $paidCount = $allEnrollments->filter(fn($e) => $e->due_days <= 0)->count();
                // Total revenue collected for this class in Revenues table
                $category = \App\Models\RevenueCategory::where('name', 'Extra Class Fee')->first();
                $totalRevenue = 0;
                if ($category) {
                    $totalRevenue = \App\Models\Revenue::where('revenue_category_id', $category->id)
                        ->whereIn('student_id', $allEnrollments->pluck('student_id'))
                        ->where('notes', 'like', "Payment for {$extraClass->name}%")
                        ->sum('amount');
                }
                $pendingRevenue = $allEnrollments->sum('due_amount');
            } else {
                $paidCount = $allEnrollments->where('paid', 1)->count();
                $totalRevenue = $allEnrollments->where('paid', 1)->sum('amount');
                $pendingRevenue = $allEnrollments->where('paid', 0)->sum('amount');
            }
            
            $collectionRate = $totalEnrollments > 0 ? round(($paidCount / $totalEnrollments) * 100) : 0;
            $feeAmount = $extraClass->fee;
        @endphp

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Total Revenue -->
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-500">Total Revenue</h3>
                    <div class="p-2 bg-green-50 rounded-lg">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-gray-800">{{ number_format($totalRevenue, 2) }}</span>
                    <span class="text-xs text-gray-500">collected</span>
                </div>
                 <div class="text-xs text-gray-400 mt-1">Pending: {{ number_format($pendingRevenue, 2) }}</div>
            </div>

            <!-- Collection Rate -->
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-500">Collection Rate</h3>
                    <div class="p-2 bg-blue-50 rounded-lg">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-gray-800">{{ $collectionRate }}%</span>
                    <span class="text-xs text-gray-500">paid</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2">
                    <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ $collectionRate }}%"></div>
                </div>
            </div>

            <!-- Enrollment -->
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-500">Enrollment</h3>
                    <div class="p-2 bg-purple-50 rounded-lg">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-gray-800">{{ $totalEnrollments }}</span>
                     <span class="text-xs text-gray-500">students</span>
                </div>
                <div class="text-xs text-gray-500 mt-1">Fee per student: {{ number_format($feeAmount, 2) }}</div>
            </div>

             <!-- Teacher (Box placeholder) -->
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-500">Instructor</h3>
                     <div class="p-2 bg-orange-50 rounded-lg">
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                </div>
                <div>
                     @if($extraClass->visitingTeacher)
                        <div class="text-lg font-bold text-gray-800 truncate">{{ $extraClass->visitingTeacher->name }}</div>
                        <div class="text-xs text-gray-500">Visiting Teacher</div>
                    @else
                        <div class="text-lg font-bold text-gray-800">Internal</div>
                         <div class="text-xs text-gray-500">School Staff</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Student List -->
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden" x-data="{ 
            search: '',
            showPayModal: false,
            payingStudent: null,
            payDays: 1,
            amountPerDay: 0,
            openPayModal(student) {
                this.payingStudent = student;
                this.payDays = student.due_days;
                this.amountPerDay = student.fee_per_day;
                this.showPayModal = true;
            }
        }">
            <!-- Pay Modal -->
            <div x-show="showPayModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div x-show="showPayModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity" aria-hidden="true">
                        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                    </div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div x-show="showPayModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Record Payment - <span x-text="payingStudent ? payingStudent.name : ''"></span>
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <form :action="'/extra-classes/{{ $extraClass->id }}/pay-daily'" method="POST" id="payForm">
                                        @csrf
                                        <input type="hidden" name="extra_class_student_id" :value="payingStudent ? payingStudent.enrollment_id : ''">
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Days to Pay</label>
                                            <input type="number" name="days" x-model="payDays" min="1" :max="payingStudent ? payingStudent.due_days : 999" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <p class="mt-1 text-xs text-gray-500" x-show="payingStudent">Max due: <span x-text="payingStudent.due_days"></span> days</p>
                                        </div>

                                        <div class="bg-gray-50 p-3 rounded-lg flex justify-between items-center mt-4">
                                            <span class="text-sm text-gray-600">Total Amount:</span>
                                            <span class="text-lg font-bold text-gray-900" x-text="new Number(payDays * amountPerDay).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                            <button type="submit" form="payForm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Confirm Payment
                            </button>
                            <button @click="showPayModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-5 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <h2 class="text-lg font-bold text-gray-800">Registered Students</h2>
                <div class="relative">
                     <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="text" x-model="search" placeholder="Filter list..." class="pl-9 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 w-64 transition-all">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Payment</th>
                            @if($extraClass->payment_type === 'daily')
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Due Days</th>
                            @endif
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($students as $row)
                            <tr class="hover:bg-gray-50 transition-colors" x-show="!search || '{{ strtolower(addslashes($row->student?->name)) }}'.includes(search.toLowerCase())">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                         <div class="flex-shrink-0 h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-xs font-bold">
                                            {{ substr($row->student?->name ?? '?', 0, 2) }}
                                        </div>
                                        <div class="ml-3">
                                            @if($extraClass->payment_type === 'daily' && $row->due_days > 0)
                                                <button type="button" 
                                                    @click="openPayModal({
                                                        id: {{ $row->student_id }},
                                                        enrollment_id: {{ $row->id }},
                                                        name: '{{ addslashes($row->student?->name) }}',
                                                        due_days: {{ $row->due_days }},
                                                        fee_per_day: {{ $row->amount ?: $extraClass->fee }}
                                                    })"
                                                    class="text-sm font-medium text-indigo-600 hover:text-indigo-900 text-left">
                                                    {{ $row->student?->name }}
                                                </button>
                                            @else
                                                <div class="text-sm font-medium text-gray-900">{{ $row->student?->name }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                 <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Enrolled
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($extraClass->payment_type === 'daily')
                                        @if($row->due_days <= 0)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                Paid
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                                                Unpaid
                                            </span>
                                        @endif
                                    @else
                                        @if($row->paid)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                Paid
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                                                Unpaid
                                            </span>
                                        @endif
                                    @endif
                                </td>
                                @if($extraClass->payment_type === 'daily')
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $row->due_days }}
                                    </td>
                                @endif
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($extraClass->payment_type === 'daily')
                                        {{ number_format($row->due_amount, 2) }}
                                    @else
                                        {{ number_format($row->amount ?? $extraClass->fee, 2) }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($students->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                    {{ $students->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
