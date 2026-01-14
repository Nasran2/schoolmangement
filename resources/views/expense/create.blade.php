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
