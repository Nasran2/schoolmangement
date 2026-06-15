<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Teacher Salary Payments</h2>
                <p class="text-gray-600 text-sm mt-1">Manage all teacher salary payments and payslips</p>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" onclick="openAdvancePaymentModal()"
                    class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium transition">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"></path>
                        <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM5 13a1 1 0 011-1h1a1 1 0 110 2H6a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    Advance Payment
                </button>
                <a class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition" href="{{ route('teacher-salary-payments.create') }}">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Payment
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @php
                $salarySettledTotal = (float) $payments->sum('base_salary');
                $paidOnSalaryDateTotal = (float) $payments->sum('amount');
                $advanceSettledTotal = (float) $payments->sum(fn($payment) => $payment->advanceSettlements->sum('amount'));
            @endphp
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif

            <!-- Filters Card -->
            <div class="bg-white rounded-lg shadow-lg border border-gray-100 mb-8">
                <div class="border-b border-gray-200 px-6 py-6 bg-gradient-to-r from-green-50 to-emerald-50">
                    <h3 class="text-lg font-semibold text-gray-800">Filter Payments</h3>
                    <p class="text-sm text-gray-600 mt-1">Refine your search by teacher, date, or month</p>
                </div>
                
                <div class="p-8">
                    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                        <!-- Teacher -->
                        <div>
                            <x-input-label for="teacher_id" :value="__('Teacher')" class="font-semibold mb-2" />
                            <select 
                                id="teacher_id" 
                                name="teacher_id" 
                                data-searchable-select
                                class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm"
                            >
                                <option value="">All Teachers</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" @selected((isset($filters['teacher_id']) ? $filters['teacher_id'] : '') == $teacher->id)>
                                        {{ $teacher->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- From Date -->
                        <div>
                            <x-input-label for="from" :value="__('From Date')" class="font-semibold mb-2" />
                            <x-text-input 
                                id="from" 
                                name="from" 
                                type="text" placeholder="DD-MM-YYYY" 
                                class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm" 
                                :value="isset($filters['from']) ? $filters['from'] : ''" 
                            />
                        </div>

                        <!-- To Date -->
                        <div>
                            <x-input-label for="to" :value="__('To Date')" class="font-semibold mb-2" />
                            <x-text-input 
                                id="to" 
                                name="to" 
                                type="text" placeholder="DD-MM-YYYY" 
                                class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm" 
                                :value="isset($filters['to']) ? $filters['to'] : ''" 
                            />
                        </div>

                        <!-- Month -->
                        <div>
                            <x-input-label for="month" :value="__('Payment Month')" class="font-semibold mb-2" />
                            <x-text-input 
                                id="month" 
                                name="month" 
                                type="month" 
                                class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm" 
                                :value="isset($filters['month']) ? $filters['month'] : ''" 
                            />
                        </div>

                        <!-- Action Button -->
                        <div class="flex items-end">
                            <button 
                                type="submit" 
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition shadow-sm"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 font-medium">Total Payments</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $payments->total() }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-lg bg-green-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"></path>
                                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 font-medium">Salary Settled</p>
                            <p class="text-3xl font-bold text-green-600 mt-2">Rs {{ number_format($salarySettledTotal, 2) }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-lg bg-green-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 font-medium">Paid On Salary Date</p>
                            <p class="text-3xl font-bold text-blue-600 mt-2">Rs {{ number_format($paidOnSalaryDateTotal, 2) }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.5 2.5a1 1 0 001.414-1.414L11 9.586V6z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                    @if($advanceSettledTotal > 0)
                        <p class="mt-2 text-xs font-semibold text-amber-700">Advance already paid: Rs {{ number_format($advanceSettledTotal, 2) }}</p>
                    @endif
                </div>

                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 font-medium">Total Deductions</p>
                            <p class="text-3xl font-bold text-red-600 mt-2">Rs {{ number_format($payments->sum('total_deductions'), 2) }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-lg bg-red-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            @if(($pendingAdvanceTotal ?? 0) > 0)
                <div class="mb-8 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    Pending teacher salary advances to deduct from future salary payments: <span class="font-bold">Rs {{ number_format($pendingAdvanceTotal, 2) }}</span>
                </div>
            @endif

            <!-- Payments Table -->
            <div class="bg-white rounded-lg shadow-lg border border-gray-100 overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-6 bg-gradient-to-r from-green-50 to-emerald-50">
                    <h3 class="text-lg font-semibold text-gray-800">Salary Payment Records</h3>
                    <p class="text-sm text-gray-600 mt-1">Complete list of all teacher salary payments</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Receipt#</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Teacher</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Month</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Date</th>
                                <th class="px-6 py-3 text-right font-semibold text-gray-700">Total Salary</th>
                                <th class="px-6 py-3 text-right font-semibold text-gray-700">Deductions</th>
                                <th class="px-6 py-3 text-right font-semibold text-gray-700">Advance Paid</th>
                                <th class="px-6 py-3 text-right font-semibold text-gray-700">Paid This Date</th>
                                <th class="px-6 py-3 text-center font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                @php
                                    $advanceSettled = (float) $payment->advanceSettlements->sum('amount');
                                @endphp
                                <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                                    <td class="px-6 py-3 font-medium text-gray-900">{{ $payment->receipt_number }}</td>
                                    <td class="px-6 py-3 text-gray-600">{{ $payment->teacher?->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-3 text-gray-600">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $payment->payment_month ? \Carbon\Carbon::parse($payment->payment_month . '-01')->format('M Y') : 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-gray-600">{{ optional($payment->paid_at)->format('M d, Y') }}</td>
                                    <td class="px-6 py-3 text-right font-semibold text-gray-900">Rs {{ number_format($payment->base_salary, 2) }}</td>
                                    <td class="px-6 py-3 text-right font-semibold text-red-600">Rs {{ number_format($payment->total_deductions, 2) }}</td>
                                    <td class="px-6 py-3 text-right font-semibold text-amber-700">Rs {{ number_format($advanceSettled, 2) }}</td>
                                    <td class="px-6 py-3 text-right font-semibold text-green-600">Rs {{ number_format($payment->amount, 2) }}</td>
                                    <td class="px-6 py-3">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('teacher-salary-payments.show', $payment) }}" 
                                               class="inline-flex items-center px-2 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-xs font-medium transition"
                                               title="View Details">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            </a>
                                            <a href="{{ route('teacher-salary-payments.receipt', $payment) }}" 
                                               class="inline-flex items-center px-2 py-1 bg-green-100 hover:bg-green-200 text-green-700 rounded text-xs font-medium transition"
                                               title="Print Receipt">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd"></path>
                                                </svg>
                                            </a>
                                            <a href="{{ route('teacher-salary-payments.payslip', $payment) }}" 
                                               class="inline-flex items-center px-2 py-1 bg-purple-100 hover:bg-purple-200 text-purple-700 rounded text-xs font-medium transition"
                                               title="Print Payslip">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.414 5L14 2.586A2 2 0 0012.586 2H9z"></path>
                                                    <path d="M3 8a2 2 0 012-2v10h8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"></path>
                                                </svg>
                                            </a>
                                            <a href="{{ route('teacher-salary-payments.edit', $payment) }}" 
                                               class="inline-flex items-center px-2 py-1 bg-yellow-100 hover:bg-yellow-200 text-yellow-700 rounded text-xs font-medium transition"
                                               title="Edit Payment">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                                </svg>
                                            </a>
                                            <form action="{{ route('teacher-salary-payments.destroy', $payment) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this salary payment? This action cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="inline-flex items-center px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded text-xs font-medium transition"
                                                        title="Delete Payment">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                                        <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <p class="font-medium">No payment records found</p>
                                        <p class="text-sm">Try adjusting your filters or create a new payment</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="border-t border-gray-200 px-6 py-4 bg-gray-50">
                    {{ $payments->links() }}
                </div>
            </div>

            <div class="mt-8 bg-white rounded-lg shadow-lg border border-gray-100 overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-6 bg-gradient-to-r from-amber-50 to-yellow-50">
                    <h3 class="text-lg font-semibold text-gray-800">Advance Salary Payments</h3>
                    <p class="text-sm text-gray-600 mt-1">Advance amounts are stored as expenses and deducted from the next full salary payment.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Date</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Teacher</th>
                                <th class="px-6 py-3 text-right font-semibold text-gray-700">Advance</th>
                                <th class="px-6 py-3 text-right font-semibold text-gray-700">Settled</th>
                                <th class="px-6 py-3 text-right font-semibold text-gray-700">Pending</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($advances->count() === 0)
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">No advance salary payments found.</td>
                                </tr>
                            @else
                                @foreach($advances as $salaryAdvance)
                                    @php
                                        $settled = (float) ($salaryAdvance->settled_amount ?? 0);
                                        $balance = max(0, (float) $salaryAdvance->amount - $settled);
                                    @endphp
                                    <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                                        <td class="px-6 py-3 text-gray-600">{{ optional($salaryAdvance->paid_at)->format('M d, Y') }}</td>
                                        <td class="px-6 py-3 font-medium text-gray-900">{{ $salaryAdvance->teacher?->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-3 text-right font-semibold text-gray-900">Rs {{ number_format($salaryAdvance->amount, 2) }}</td>
                                        <td class="px-6 py-3 text-right font-semibold text-blue-600">Rs {{ number_format($settled, 2) }}</td>
                                        <td class="px-6 py-3 text-right font-semibold {{ $balance > 0 ? 'text-amber-700' : 'text-green-600' }}">Rs {{ number_format($balance, 2) }}</td>
                                        <td class="px-6 py-3 text-gray-600">{{ $salaryAdvance->notes ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-200 px-6 py-4 bg-gray-50">
                    {{ $advances->links() }}
                </div>
            </div>
        </div>
    </div>

    @include('teacher-salary-payments._advance-payment-modal', ['teachers' => $teachers])
</x-app-layout>
