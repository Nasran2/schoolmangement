<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Owner bank account</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div role="status" class="rounded-lg bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Transfer hand cash</h3>
                        <p class="mt-1 text-sm text-gray-600">Record cash deposited into the Owner bank account. This reduces cash in hand without recording an expense or changing profit. The owner's bank balance and subsequent spending are not tracked.</p>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Cash in hand today</div>
                        <div class="text-xl font-bold text-gray-900">Rs {{ number_format($cashInHand, 2) }}</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('owner-transfers.store') }}" class="space-y-4" x-data="{ saving: false }" @submit="saving = true">
                    @csrf
                    <input type="hidden" name="submission_id" value="{{ old('submission_id', $submissionId) }}">
                    <x-input-error :messages="$errors->get('submission_id')" />
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="transferred_at" value="Transfer date" />
                            <x-text-input id="transferred_at" name="transferred_at" type="date" class="mt-1 block w-full" :value="old('transferred_at', now()->toDateString())" :max="now()->toDateString()" required />
                            <x-input-error :messages="$errors->get('transferred_at')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="amount" value="Amount (Rs)" />
                            <x-text-input id="amount" name="amount" type="number" min="0.01" max="9999999999.99" step="0.01" class="mt-1 block w-full" :value="old('amount')" required />
                            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="destination" value="Transfer to" />
                            <x-text-input id="destination" type="text" value="Owner bank account" class="mt-1 block w-full bg-gray-50" readonly />
                        </div>
                        <div>
                            <x-input-label for="reference" value="Deposit reference (optional)" />
                            <x-text-input id="reference" name="reference" type="text" maxlength="120" class="mt-1 block w-full" :value="old('reference')" />
                            <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="notes" value="Notes (optional)" />
                        <textarea id="notes" name="notes" maxlength="2000" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('notes') }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </div>
                    <x-primary-button x-bind:disabled="saving" x-text="saving ? 'Saving…' : 'Record transfer'">Record transfer</x-primary-button>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Transfers to Owner bank account</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm divide-y divide-gray-200">
                        <thead class="bg-gray-50"><tr>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-left">Destination</th>
                            <th class="px-4 py-3 text-left">Reference / Notes</th>
                            <th class="px-4 py-3 text-left">Recorded by</th>
                            <th class="px-4 py-3 text-right">Amount (Rs)</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($transfers as $transfer)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $transfer->transferred_at->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3">Owner bank account</td>
                                    <td class="px-4 py-3"><div>{{ $transfer->reference ?: '—' }}</div><div class="text-gray-500">{{ $transfer->notes }}</div></td>
                                    <td class="px-4 py-3">{{ $transfer->creator?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right font-mono">{{ number_format($transfer->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-6 text-gray-500">No owner transfers recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $transfers->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
