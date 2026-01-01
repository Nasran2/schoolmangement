<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Teacher Details</h2>
                <p class="text-gray-600 text-sm mt-1">View and manage teacher information and salary payments</p>
            </div>
            <div class="flex gap-3">
                @can('teachers.manage')
                    <a href="{{ route('teachers.edit', $teacher) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg shadow hover:bg-blue-700 transition-all">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </a>
                @endcan
                <a href="{{ route('teachers.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition-all">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm font-medium text-green-800">{{ session('status') }}</span>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Teacher Profile Card -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                        <!-- Header with Gradient -->
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-6 text-center">
                            <div class="bg-white/20 backdrop-blur-sm rounded-full p-4 w-20 h-20 mx-auto mb-3 flex items-center justify-center">
                                <svg class="h-12 w-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white">{{ $teacher->name }}</h3>
                            <span class="inline-block mt-2 px-3 py-1 text-xs font-semibold text-white bg-white/20 backdrop-blur-sm rounded-full">
                                {{ $teacher->active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <!-- Profile Information -->
                        <div class="p-6 space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="bg-blue-100 rounded-lg p-2">
                                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="text-xs font-semibold text-gray-500 uppercase">Email</div>
                                    <div class="text-sm text-gray-900 mt-1">{{ $teacher->email ?? 'Not provided' }}</div>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="bg-green-100 rounded-lg p-2">
                                    <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="text-xs font-semibold text-gray-500 uppercase">Phone</div>
                                    <div class="text-sm text-gray-900 mt-1">{{ $teacher->phone ?? 'Not provided' }}</div>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="bg-purple-100 rounded-lg p-2">
                                    <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="text-xs font-semibold text-gray-500 uppercase">Address</div>
                                    <div class="text-sm text-gray-900 mt-1">{{ $teacher->address ?? 'Not provided' }}</div>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="bg-yellow-100 rounded-lg p-2">
                                    <svg class="h-5 w-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="text-xs font-semibold text-gray-500 uppercase">Joining Date</div>
                                    <div class="text-sm text-gray-900 mt-1">{{ optional($teacher->joining_date)->format('M d, Y') ?? 'Not specified' }}</div>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="bg-indigo-100 rounded-lg p-2">
                                    <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="text-xs font-semibold text-gray-500 uppercase">Assigned Classes</div>
                                    <div class="text-sm text-gray-900 mt-1">{{ $teacher->assigned_classes ?? 'Not assigned' }}</div>
                                </div>
                            </div>

                            <!-- Salary Card -->
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <div class="bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="text-xs font-semibold text-green-700 uppercase">Monthly Salary</div>
                                            <div class="text-2xl font-bold text-green-700 mt-1">Rs {{ number_format($teacher->salary_amount, 2) }}</div>
                                        </div>
                                        <div class="bg-green-200 rounded-full p-3">
                                            <svg class="h-6 w-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment History Card -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Monthly Payment Tracker -->
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Monthly Payment Tracker</h3>
                                <p class="text-sm text-gray-600 mt-1">Track salary payments by month</p>
                            </div>
                            @can('teachers.salary.pay')
                                <a href="{{ route('teacher-salary-payments.create', ['teacher_id' => $teacher->id]) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 text-white text-sm font-semibold rounded-lg shadow hover:from-green-700 hover:to-emerald-700 transition-all">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Record Payment
                                </a>
                            @endcan
                        </div>

                        @php
                            // Get payments by month for the current and past year
                            $monthlyPayments = [];
                            $startMonth = now()->subMonths(11)->startOfMonth();
                            $endMonth = now()->endOfMonth();
                            
                            // Fetch all payments in this date range
                            $paymentsCollection = \App\Models\TeacherSalaryPayment::where('teacher_id', $teacher->id)
                                ->whereBetween('paid_at', [$startMonth, $endMonth])
                                ->get()
                                ->groupBy(function($payment) {
                                    return $payment->paid_at->format('Y-m');
                                });
                            
                            // Build 12-month array
                            for($i = 0; $i < 12; $i++) {
                                $month = now()->subMonths(11 - $i)->format('Y-m');
                                $monthLabel = now()->subMonths(11 - $i)->format('M Y');
                                $monthlyPayments[] = [
                                    'month' => $month,
                                    'label' => $monthLabel,
                                    'paid' => isset($paymentsCollection[$month]) && $paymentsCollection[$month]->count() > 0,
                                    'amount' => isset($paymentsCollection[$month]) ? $paymentsCollection[$month]->sum('amount') : 0,
                                    'count' => isset($paymentsCollection[$month]) ? $paymentsCollection[$month]->count() : 0,
                                ];
                            }
                        @endphp

                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                            @foreach($monthlyPayments as $mp)
                                <div class="relative group">
                                    <div class="border-2 {{ $mp['paid'] ? 'border-green-500 bg-green-50' : 'border-gray-200 bg-gray-50' }} rounded-lg p-3 text-center hover:shadow-md transition-all cursor-pointer">
                                        <div class="text-xs font-semibold {{ $mp['paid'] ? 'text-green-700' : 'text-gray-500' }} mb-1">{{ $mp['label'] }}</div>
                                        @if($mp['paid'])
                                            <svg class="h-6 w-6 text-green-600 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            <div class="text-xs font-bold text-green-700 mt-1">Paid</div>
                                        @else
                                            <svg class="h-6 w-6 text-gray-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            <div class="text-xs font-semibold text-gray-500 mt-1">Unpaid</div>
                                        @endif
                                    </div>
                                    @if($mp['paid'])
                                        <!-- Tooltip -->
                                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-10">
                                            Rs {{ number_format($mp['amount'], 2) }} ({{ $mp['count'] }} {{ $mp['count'] > 1 ? 'payments' : 'payment' }})
                                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Payment History Table -->
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Recent Payment History</h3>
                        
                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Receipt</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Month</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Base Salary</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Deductions</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Net Amount</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @forelse ($payments as $p)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3 text-sm">
                                                <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">{{ $p->receipt_number }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                @if($p->payment_month)
                                                    <span class="inline-block px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-700 rounded">
                                                        {{ \Carbon\Carbon::parse($p->payment_month . '-01')->format('M Y') }}
                                                    </span>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ optional($p->paid_at)->format('M d, Y') }}</td>
                                            <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">Rs {{ number_format($p->base_salary ?? $p->amount, 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-right">
                                                @if($p->total_deductions > 0)
                                                    <span class="text-red-600 font-semibold">-Rs {{ number_format($p->total_deductions, 2) }}</span>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right font-bold text-green-600">Rs {{ number_format($p->amount, 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <a href="{{ route('teacher-salary-payments.show', $p) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="View">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                        </svg>
                                                    </a>
                                                    <a href="{{ route('teacher-salary-payments.receipt', $p) }}" class="p-1.5 text-green-600 hover:bg-green-50 rounded-lg transition-all" title="Print Receipt">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                                        </svg>
                                                    </a>
                                                    @can('teachers.salary.pay')
                                                        <span x-data="{ open:false }">
                                                            <button type="button" x-on:click="open=true" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Delete">
                                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                </svg>
                                                            </button>
                                                            <form x-ref="delForm" class="hidden" method="POST" action="{{ route('teacher-salary-payments.destroy', $p) }}">
                                                                @csrf
                                                                @method('DELETE')
                                                            </form>
                                                            <div x-cloak x-show="open" class="fixed inset-0 z-50 flex items-center justify-center">
                                                                <div class="absolute inset-0 bg-black/40" x-on:click="open=false"></div>
                                                                <div class="relative z-10 w-full max-w-sm rounded-xl bg-white p-6 shadow-2xl">
                                                                    <h3 class="text-lg font-bold text-gray-900 mb-2">Delete Payment</h3>
                                                                    <p class="text-sm text-gray-600 mb-6">Are you sure you want to delete this salary payment?</p>
                                                                    <div class="flex justify-end gap-3">
                                                                        <button type="button" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50" x-on:click="open=false">Cancel</button>
                                                                        <button type="button" class="px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700" x-on:click="$refs.delForm.submit()">Delete</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </span>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-4 py-8 text-center">
                                                <svg class="h-12 w-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                <p class="text-sm text-gray-600">No salary payments recorded yet.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($payments->hasPages())
                            <div class="mt-4">
                                {{ $payments->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
