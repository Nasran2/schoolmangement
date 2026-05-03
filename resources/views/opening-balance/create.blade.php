<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Set Opening Balance') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ obDate: '{{ \Carbon\Carbon::yesterday()->toDateString() }}', obAmount: '0.00' }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-sm text-gray-600 mb-6">Enter the opening balance for a specific date (usually yesterday's date for today's ledger). If not entered, the daily ledger will default to 0 for that date.</p>

                    @if(session('success'))
                        <div class="mb-4 bg-emerald-50 text-emerald-700 p-4 rounded-md text-sm">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('opening-balance.store') }}" class="flex flex-col sm:flex-row items-end gap-4">
                        @csrf
                        <div class="flex-1 w-full">
                            <x-input-label for="ob_date" :value="__('Date')" />
                            <x-text-input id="ob_date" name="date" type="date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" x-model="obDate" required />
                        </div>
                        <div class="flex-1 w-full">
                            <x-input-label for="ob_amount" :value="__('Opening Balance Amount')" />
                            <x-text-input id="ob_amount" name="amount" type="number" step="0.01" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" x-model="obAmount" required />
                        </div>
                        <div class="w-full sm:w-auto">
                            <button type="submit" class="inline-flex w-full sm:w-auto justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 shadow focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Save Opening Balance
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Opening Balance History</h3>
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount (Rs)</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($balances as $balance)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $balance->date->format('Y-m-d') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-mono">
                                            {{ number_format($balance->amount, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4">
                                            <button type="button" @click="obDate = '{{ $balance->date->toDateString() }}'; obAmount = '{{ $balance->amount }}'; window.scrollTo({ top: 0, behavior: 'smooth' })" class="text-indigo-600 hover:text-indigo-900 font-semibold transition">Edit</button>
                                            <form action="{{ route('opening-balance.destroy', $balance) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this opening balance?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:text-rose-900 font-semibold transition">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500">
                                            No opening balances recorded yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
