<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Edit Salary Payment</h2>
                <p class="text-gray-600 text-sm mt-1">Update teacher salary payment details</p>
            </div>
            <a class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition" href="{{ route('teacher-salary-payments.show', $payment) }}">← Back</a>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('teacher-salary-payments.update', $payment) }}" class="bg-white rounded-lg shadow-lg border border-gray-100">
                @csrf
                @method('PUT')

                <!-- Header -->
                <div class="border-b border-gray-200 px-8 py-6 bg-gradient-to-r from-blue-600 to-indigo-600">
                    <h3 class="text-xl font-bold text-white">Salary Payment Details</h3>
                    <p class="text-blue-100 text-sm mt-1">Fill in the payment information below</p>
                </div>

                <div class="p-8 space-y-8">
                    <!-- Basic Information Section -->
                    <div class="border-l-4 border-blue-500 pl-6">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                            </svg>
                            Basic Information
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Teacher -->
                            <div>
                                <x-input-label for="teacher_id" :value="__('Teacher *')" class="mb-2 font-semibold" />
                                <select id="teacher_id" name="teacher_id" data-searchable-select required
                                    class="block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm"
                                    onchange="updateBaseSalary()">
                                    <option value="">Select Teacher</option>
                                    @foreach($teachers as $teacher)
                                        @php
                                            $basicSalaryAmount = (float) data_get(
                                                collect($teacher->salary_components ?? [])->firstWhere('type', 'Basic Salary'),
                                                'amount',
                                                0
                                            );
                                        @endphp
                                        <option value="{{ $teacher->id }}" 
                                                data-salary="{{ $teacher->salary_amount }}"
                                                data-basic-salary="{{ $basicSalaryAmount }}"
                                                @selected(old('teacher_id', $payment->teacher_id) == $teacher->id)>
                                            {{ $teacher->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('teacher_id')" class="mt-2" />
                            </div>

                            <!-- Payment Month -->
                            <div>
                                <x-input-label for="payment_month" :value="__('Payment Month *')" class="mb-2 font-semibold" />
                                <x-text-input 
                                    id="payment_month" 
                                    name="payment_month" 
                                    type="month" 
                                    class="block w-full" 
                                    :value="old('payment_month', $payment->payment_month)" 
                                    required 
                                />
                                <x-input-error :messages="$errors->get('payment_month')" class="mt-2" />
                            </div>

                            <!-- Payment Date -->
                            <div>
                                <x-input-label for="paid_at" :value="__('Payment Date *')" class="mb-2 font-semibold" />
                                <x-text-input 
                                    id="paid_at" 
                                    name="paid_at" 
                                    type="text" placeholder="DD-MM-YYYY" 
                                    class="block w-full" 
                                    :value="old('paid_at', optional($payment->paid_at)->format('d-m-Y'))" 
                                    required 
                                />
                                <x-input-error :messages="$errors->get('paid_at')" class="mt-2" />
                            </div>

                            <!-- Payment Method -->
                            <div>
                                <x-input-label for="payment_method" :value="__('Payment Method')" class="mb-2 font-semibold" />
                                <div class="flex items-center gap-4">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="payment_method" value="cash" class="text-blue-600" {{ old('payment_method', $payment->payment_method ?? 'cash') === 'cash' ? 'checked' : '' }} />
                                        <span>Cash</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="payment_method" value="bank" class="text-blue-600" {{ old('payment_method', $payment->payment_method) === 'bank' ? 'checked' : '' }} />
                                        <span>Bank</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="payment_method" value="cheque" class="text-blue-600" {{ old('payment_method', $payment->payment_method) === 'cheque' ? 'checked' : '' }} />
                                        <span>Cheque</span>
                                    </label>
                                </div>
                                <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
                            </div>

                            <!-- Bank Details (shown for Bank/Cheque) -->
                            <div id="bank_details" class="md:col-span-2 hidden">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-2 bg-blue-50 p-4 rounded-lg border border-blue-200">
                                    <div>
                                        <x-input-label for="bank_name" :value="__('Bank Name')" class="mb-2 font-semibold" />
                                        <x-text-input id="bank_name" name="bank_name" type="text" class="block w-full" :value="old('bank_name', $payment->bank_name)" />
                                    </div>
                                    <div>
                                        <x-input-label for="bank_branch" :value="__('Branch')" class="mb-2 font-semibold" />
                                        <x-text-input id="bank_branch" name="bank_branch" type="text" class="block w-full" :value="old('bank_branch', $payment->bank_branch)" />
                                    </div>
                                    <div>
                                        <x-input-label for="bank_account_no" :value="__('Account No.')" class="mb-2 font-semibold" />
                                        <x-text-input id="bank_account_no" name="bank_account_no" type="text" class="block w-full" :value="old('bank_account_no', $payment->bank_account_no)" />
                                    </div>
                                </div>
                            </div>

                            <!-- Total Salary -->
                            <div>
                                <x-input-label for="base_salary" :value="__('Total Salary (Rs) *')" class="mb-2 font-semibold" />
                                <div class="relative">
                                    <span class="absolute left-3 top-3 text-gray-500 font-medium">Rs</span>
                                    <x-text-input 
                                        id="base_salary" 
                                        name="base_salary" 
                                        type="number" 
                                        step="0.01" 
                                        class="block w-full pl-12" 
                                        :value="old('base_salary', $payment->base_salary)" 
                                        required 
                                        oninput="calculateTotal()"
                                    />
                                </div>
                                <x-input-error :messages="$errors->get('base_salary')" class="mt-2" />

                                <div class="mt-4">
                                    <x-input-label for="basic_salary_for_epf" :value="__('Basic Salary (for EPF/ETF)')" class="mb-2 font-semibold" />
                                    <div class="relative">
                                        <span class="absolute left-3 top-3 text-gray-500 font-medium">Rs</span>
                                        <x-text-input id="basic_salary_for_epf" type="number" step="0.01" readonly
                                            class="block w-full pl-12 bg-gray-50" value="" />
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">EPF/ETF is calculated only from Basic Salary.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Deductions Section -->
                    <div class="border-l-4 border-red-500 pl-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-semibold text-gray-800 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                Deductions (Optional)
                            </h4>
                            <button 
                                type="button" 
                                onclick="addDeduction()" 
                                class="inline-flex items-center px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition shadow-sm"
                            >
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
                                </svg>
                                Add Deduction
                            </button>
                        </div>

                        <div id="deductions-container" class="space-y-3">
                            <!-- Deductions will be added here dynamically -->
                        </div>

                        <p class="text-sm text-gray-500 mt-3 italic">Examples: Leaves, Advance, Loan, Late Arrivals, etc.</p>
                    </div>

                    <!-- Summary Section -->
                    <div class="border-l-4 border-green-500 pl-6 bg-gray-50 rounded-lg p-6">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                            </svg>
                            Payment Summary
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-white rounded-lg p-4 border border-gray-200">
                                <p class="text-sm text-gray-600 font-medium">Total Salary</p>
                                <p class="text-2xl font-bold text-blue-600 mt-1" id="display-base-salary">Rs 0.00</p>
                            </div>
                            <div class="bg-white rounded-lg p-4 border border-gray-200">
                                <p class="text-sm text-gray-600 font-medium">Total Deductions</p>
                                <p class="text-2xl font-bold text-red-600 mt-1" id="display-deductions">Rs 0.00</p>
                            </div>
                            <div class="bg-white rounded-lg p-4 border border-green-200 bg-green-50">
                                <p class="text-sm text-green-700 font-medium">Paid This Date</p>
                                <p class="text-2xl font-bold text-green-600 mt-1" id="display-net-amount">Rs 0.00</p>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="border-l-4 border-gray-400 pl-6">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 13V5a2 2 0 00-2-2H4a2 2 0 00-2 2v8a2 2 0 002 2h3l3 3 3-3h3a2 2 0 002-2zM5 7a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm1 3a1 1 0 100 2h3a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                            </svg>
                            Additional Notes (Optional)
                        </h4>

                        <textarea 
                            id="notes" 
                            name="notes" 
                            rows="3" 
                            class="block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm"
                            placeholder="Any additional notes or comments about this payment..."
                        >{{ old('notes', $payment->notes) }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="border-t border-gray-200 px-8 py-6 bg-gray-50 flex items-center justify-between">
                    <a href="{{ route('teacher-salary-payments.index') }}" class="text-gray-600 hover:text-gray-800 font-medium">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-sm transition">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let deductionIndex = 0;

        // Pre-load existing deductions
        document.addEventListener('DOMContentLoaded', function() {
            @if($payment->deductions && count($payment->deductions) > 0)
                @foreach($payment->deductions as $index => $deduction)
                    addDeduction('{{ addslashes($deduction["reason"]) }}', {{ $deduction["amount"] }});
                @endforeach
            @endif
            calculateTotal();
            updateBaseSalary(false);
        });

        function updateBaseSalary(alsoSetTotal = true) {
            const select = document.getElementById('teacher_id');
            const selected = select?.options?.[select.selectedIndex];
            const basicSalary = parseFloat(selected?.dataset?.basicSalary || 0) || 0;
            const basicInput = document.getElementById('basic_salary_for_epf');
            if (basicInput) basicInput.value = basicSalary > 0 ? basicSalary.toFixed(2) : '';

            if (!alsoSetTotal) return;
            const totalSalary = parseFloat(selected?.dataset?.salary || 0) || 0;
            const totalInput = document.getElementById('base_salary');
            if (totalInput && totalSalary > 0) totalInput.value = totalSalary.toFixed(2);
            calculateTotal();
        }

        function addDeduction(reason = '', amount = '') {
            const container = document.getElementById('deductions-container');
            const deductionHtml = `
                <div class="deduction-row flex gap-3 items-start bg-white p-4 rounded-lg border border-gray-200">
                    <div class="flex-1">
                        <input 
                            type="text" 
                            name="deductions[${deductionIndex}][reason]" 
                            value="${reason}"
                            placeholder="Reason (e.g., Leaves, Advance, Loan)" 
                            class="block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-lg shadow-sm text-sm"
                            required
                        />
                    </div>
                    <div class="w-40">
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-gray-500 text-sm">Rs</span>
                            <input 
                                type="number" 
                                name="deductions[${deductionIndex}][amount]" 
                                value="${amount}"
                                step="0.01" 
                                min="0"
                                placeholder="0.00" 
                                class="block w-full pl-10 border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-lg shadow-sm text-sm"
                                oninput="calculateTotal()"
                                required
                            />
                        </div>
                    </div>
                    <button 
                        type="button" 
                        onclick="removeDeduction(this)" 
                        class="p-2 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg transition"
                        title="Remove"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', deductionHtml);
            deductionIndex++;
        }

        function removeDeduction(button) {
            button.closest('.deduction-row').remove();
            calculateTotal();
        }

        function calculateTotal() {
            const baseSalary = parseFloat(document.getElementById('base_salary').value) || 0;
            
            // Calculate total deductions
            let totalDeductions = 0;
            document.querySelectorAll('.deduction-row input[type="number"]').forEach(input => {
                totalDeductions += parseFloat(input.value) || 0;
            });
            
            const netAmount = baseSalary - totalDeductions;
            
            // Update display
            document.getElementById('display-base-salary').textContent = `Rs ${baseSalary.toFixed(2)}`;
            document.getElementById('display-deductions').textContent = `Rs ${totalDeductions.toFixed(2)}`;
            document.getElementById('display-net-amount').textContent = `Rs ${netAmount.toFixed(2)}`;
        }

        function syncBankDetailsVisibility() {
            const method = (document.querySelector('input[name="payment_method"]:checked')?.value || '').toLowerCase();
            const bankBox = document.getElementById('bank_details');
            if (!bankBox) return;
            if (method === 'bank' || method === 'cheque') {
                bankBox.classList.remove('hidden');
            } else {
                bankBox.classList.add('hidden');
            }
        }

        function initPaymentMethodWatcher() {
            document.querySelectorAll('input[name="payment_method"]').forEach(r => {
                r.addEventListener('change', syncBankDetailsVisibility);
            });
            syncBankDetailsVisibility();
        }

        // Initialize calculation on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateTotal();
            initPaymentMethodWatcher();
        });
    </script>
</x-app-layout>
