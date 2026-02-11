<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Expense</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('expense.items.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="expense_category_id" :value="__('Category')" />
                            <select id="expense_category_id" name="expense_category_id" class="mt-1 block w-full rounded-md border-gray-300">
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('expense_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('expense_category_id')" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="amount" :value="__('Amount')" />
                                <x-text-input id="amount" name="amount" type="number" step="0.01" class="mt-1 block w-full" :value="old('amount')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                            </div>
                            <div>
                                <x-input-label for="expense_date" :value="__('Date')" />
                                <x-text-input id="expense_date" name="expense_date" type="text" placeholder="DD-MM-YYYY" class="mt-1 block w-full" :value="old('expense_date', now()->format('d-m-Y'))" required />
                                <x-input-error class="mt-2" :messages="$errors->get('expense_date')" />
                            </div>
                        </div>

                        @php
                            $pm = old('payment_method', 'cash');
                        @endphp

                        <div x-data="{ pm: '{{ $pm }}' }">
                            <x-input-label :value="__('Payment Method')" />

                            <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <label class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer" :class="pm==='cash' ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200'">
                                    <input type="radio" name="payment_method" value="cash" class="mt-1" x-model="pm">
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">Cash</div>
                                        <div class="text-xs text-gray-500">No extra details</div>
                                    </div>
                                </label>
                                <label class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer" :class="pm==='bank_transfer' ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200'">
                                    <input type="radio" name="payment_method" value="bank_transfer" class="mt-1" x-model="pm">
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">Bank Transfer</div>
                                        <div class="text-xs text-gray-500">Ref no + Bank</div>
                                    </div>
                                </label>
                                <label class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer" :class="pm==='cheque' ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200'">
                                    <input type="radio" name="payment_method" value="cheque" class="mt-1" x-model="pm">
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">Cheque</div>
                                        <div class="text-xs text-gray-500">Cheque details</div>
                                    </div>
                                </label>
                            </div>

                            <div x-show="pm === 'bank_transfer'" x-cloak class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="bank_name" :value="__('Bank Name')" />
                                    <x-text-input id="bank_name" name="bank_name" type="text" class="mt-1 block w-full" :value="old('bank_name')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('bank_name')" />
                                </div>
                                <div>
                                    <x-input-label for="bank_ref_no" :value="__('Reference No (optional)')" />
                                    <x-text-input id="bank_ref_no" name="bank_ref_no" type="text" class="mt-1 block w-full" :value="old('bank_ref_no')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('bank_ref_no')" />
                                </div>
                            </div>

                            <div x-show="pm === 'cheque'" x-cloak class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="cheque_date" :value="__('Cheque Date')" />
                                    <x-text-input id="cheque_date" name="cheque_date" type="date" class="mt-1 block w-full" :value="old('cheque_date')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('cheque_date')" />
                                </div>
                                <div>
                                    <x-input-label for="cheque_number" :value="__('Cheque Number')" />
                                    <x-text-input id="cheque_number" name="cheque_number" type="text" class="mt-1 block w-full" :value="old('cheque_number')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('cheque_number')" />
                                </div>
                                <div class="sm:col-span-2">
                                    <x-input-label for="cheque_bank" :value="__('Bank (on cheque)')" />
                                    <x-text-input id="cheque_bank" name="cheque_bank" type="text" class="mt-1 block w-full" :value="old('cheque_bank')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('cheque_bank')" />
                                </div>
                            </div>
                        </div>

                        <div>
                            <x-input-label for="notes" :value="__('Notes (optional)')" />
                            <textarea id="notes" name="notes" class="mt-1 block w-full rounded-md border-gray-300" rows="3">{{ old('notes') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Save</x-primary-button>
                            <a href="{{ route('expense.items.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
