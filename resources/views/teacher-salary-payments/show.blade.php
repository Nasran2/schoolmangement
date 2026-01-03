<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Payment Details</h2>
                <p class="text-gray-600 text-sm mt-1">Receipt #{{ $payment->receipt_number }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('teacher-salary-payments.receipt', $payment) }}" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd"></path>
                    </svg>
                    Print Receipt
                </a>
                <a href="{{ route('teacher-salary-payments.payslip', $payment) }}" 
                   class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.414 5L14 2.586A2 2 0 0012.586 2H9z"></path>
                        <path d="M3 8a2 2 0 012-2v10h8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"></path>
                    </svg>
                    Print Payslip
                </a>

                <form method="POST" action="{{ route('teacher-salary-payments.email-payslip', $payment) }}">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/>
                        </svg>
                        Send Payslip Email
                    </button>
                </form>
                <a href="{{ route('teacher-salary-payments.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition">
                    ← Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Payment Info Card -->
            <div class="bg-white rounded-lg shadow-lg border border-gray-100 mb-6">
                <div class="border-b border-gray-200 px-8 py-6 bg-gradient-to-r from-green-50 to-emerald-50">
                    <h3 class="text-xl font-bold text-gray-800">Payment Information</h3>
                </div>

                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Teacher Name</p>
                        <p class="text-lg font-semibold text-gray-900 mt-1">{{ $payment->teacher?->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Receipt Number</p>
                        <p class="text-lg font-semibold text-gray-900 mt-1">{{ $payment->receipt_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Payment Month</p>
                        <p class="text-lg font-semibold text-gray-900 mt-1">
                            {{ $payment->payment_month ? \Carbon\Carbon::parse($payment->payment_month . '-01')->format('F Y') : 'N/A' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Payment Date</p>
                        <p class="text-lg font-semibold text-gray-900 mt-1">{{ optional($payment->paid_at)->format('F d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Processed By</p>
                        <p class="text-lg font-semibold text-gray-900 mt-1">{{ $payment->creator?->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Created At</p>
                        <p class="text-lg font-semibold text-gray-900 mt-1">{{ $payment->created_at->format('F d, Y - h:i A') }}</p>
                    </div>
                </div>
            </div>

            <!-- Salary Breakdown Card -->
            <div class="bg-white rounded-lg shadow-lg border border-gray-100 mb-6">
                <div class="border-b border-gray-200 px-8 py-6 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <h3 class="text-xl font-bold text-gray-800">Salary Breakdown</h3>
                </div>

                <div class="p-8">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center py-3 border-b border-gray-200">
                            <span class="text-gray-700 font-medium">Base Salary</span>
                            <span class="text-xl font-bold text-gray-900">Rs {{ number_format($payment->base_salary, 2) }}</span>
                        </div>

                        @if($payment->deductions && count($payment->deductions) > 0)
                            <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                                <h4 class="text-sm font-semibold text-red-800 mb-3 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    Deductions
                                </h4>
                                @foreach($payment->deductions as $deduction)
                                    <div class="flex justify-between items-center py-2 text-sm">
                                        <span class="text-red-700">{{ $deduction['reason'] }}</span>
                                        <span class="font-semibold text-red-700">- Rs {{ number_format($deduction['amount'], 2) }}</span>
                                    </div>
                                @endforeach
                                <div class="flex justify-between items-center py-2 mt-2 border-t border-red-300">
                                    <span class="text-red-800 font-medium">Total Deductions</span>
                                    <span class="text-lg font-bold text-red-800">Rs {{ number_format($payment->total_deductions, 2) }}</span>
                                </div>
                            </div>
                        @else
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 text-center">
                                <p class="text-gray-600">No deductions applied</p>
                            </div>
                        @endif

                        <div class="flex justify-between items-center py-4 bg-green-50 rounded-lg px-4 border-2 border-green-300 mt-4">
                            <span class="text-lg font-bold text-green-800">Net Amount Paid</span>
                            <span class="text-2xl font-bold text-green-600">Rs {{ number_format($payment->amount, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Card -->
            @if($payment->notes)
                <div class="bg-white rounded-lg shadow-lg border border-gray-100">
                    <div class="border-b border-gray-200 px-8 py-6 bg-gradient-to-r from-gray-50 to-gray-100">
                        <h3 class="text-xl font-bold text-gray-800">Additional Notes</h3>
                    </div>

                    <div class="p-8">
                        <p class="text-gray-700 whitespace-pre-line">{{ $payment->notes }}</p>
                    </div>
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="mt-6 flex gap-3">
                <a href="{{ route('teacher-salary-payments.edit', $payment) }}" 
                   class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                    </svg>
                    Edit Payment
                </a>
                
                <form method="POST" action="{{ route('teacher-salary-payments.destroy', $payment) }}" class="flex-1" onsubmit="return confirm('Are you sure you want to delete this payment record?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="w-full inline-flex items-center justify-center px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        Delete Payment
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
