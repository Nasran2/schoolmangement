<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Opening Balance</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @if ($errors->has('opening_balance'))
                <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800">{{ $errors->first('opening_balance') }}</div>
            @endif

            <div class="mb-4 rounded-md bg-blue-50 p-4 text-sm text-blue-900">
                <div class="font-semibold">One-time setup</div>
                <div class="mt-1 text-blue-800">Enter the old Cash and Bank balances from before this system started. Once saved, this cannot be edited.</div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-lg border bg-gray-50 p-4">
                            <div class="text-xs text-gray-600">As of</div>
                            <div class="text-base font-semibold text-gray-900">{{ $as_of ?: '—' }}</div>
                        </div>
                        <div class="rounded-lg border bg-gray-50 p-4">
                            <div class="text-xs text-gray-600">Cash</div>
                            <div class="text-base font-semibold text-gray-900">{{ $cash_amount !== '' ? number_format((float) $cash_amount, 2) : '—' }}</div>
                        </div>
                        <div class="rounded-lg border bg-gray-50 p-4">
                            <div class="text-xs text-gray-600">Bank</div>
                            <div class="text-base font-semibold text-gray-900">{{ $bank_amount !== '' ? number_format((float) $bank_amount, 2) : '—' }}</div>
                        </div>
                    </div>

                    @if ($locked)
                        <div class="rounded-md bg-amber-50 p-4 text-sm text-amber-900">
                            <div class="font-semibold">Locked</div>
                            <div class="mt-1">Opening balance was already saved{{ $set_at ? ' on ' . $set_at : '' }}{{ $set_by ? ' (user ID: ' . $set_by . ')' : '' }}.</div>
                        </div>

                        @canany(['settings.manage','settings.opening_balance.reset'])
                            <div class="mt-4 rounded-md bg-red-50 p-4 text-sm text-red-900 border border-red-100">
                                <div class="font-semibold">Reset (Admin only)</div>
                                <div class="mt-1">This will remove the saved opening balance and unlock the form so it can be set again.</div>

                                <form method="POST" action="{{ route('settings.opening-balance.reset') }}" class="mt-3"
                                    onsubmit="return confirm('Reset opening balance? This will unlock the form and allow re-setting the balances.');">
                                    @csrf
                                    <x-danger-button type="submit">Reset Opening Balance</x-danger-button>
                                </form>
                            </div>
                        @endcanany
                    @endif

                    <form method="POST" action="{{ route('settings.opening-balance.update') }}" class="mt-6 space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="as_of" :value="__('As of date')" />
                            <x-text-input id="as_of" name="as_of" type="date" class="mt-1 block w-full" :value="old('as_of', $as_of ?: now()->toDateString())" :disabled="$locked" />
                            <p class="mt-1 text-xs text-gray-600">This should be the day you start using the system.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('as_of')" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="cash_amount" :value="__('Old Cash Balance')" />
                                <x-text-input id="cash_amount" name="cash_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('cash_amount', $cash_amount !== '' ? $cash_amount : '0.00')" :disabled="$locked" />
                                <x-input-error class="mt-2" :messages="$errors->get('cash_amount')" />
                            </div>

                            <div>
                                <x-input-label for="bank_amount" :value="__('Old Bank Balance')" />
                                <x-text-input id="bank_amount" name="bank_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('bank_amount', $bank_amount !== '' ? $bank_amount : '0.00')" :disabled="$locked" />
                                <x-input-error class="mt-2" :messages="$errors->get('bank_amount')" />
                            </div>
                        </div>

                        @unless($locked)
                            <div class="flex items-center gap-4">
                                <x-primary-button>Save Opening Balance</x-primary-button>
                            </div>
                        @endunless
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
