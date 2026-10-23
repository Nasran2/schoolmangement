<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Owner bank account</h2>
    </x-slot>

    @include('owner-transfers.styles')
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div role="status" class="rounded-lg bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div role="alert" class="rounded-lg p-4" style="background:#fff1f2;color:#b91c1c;">
                    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Transfer hand cash</h3>
                        <p class="mt-1 text-sm text-gray-600">Record cash deposited into the Owner bank account. This reduces cash in hand without recording an expense or changing profit. Returns bring cash back into hand and reduce the net transfer. The owner's bank balance and subsequent spending are not tracked.</p>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Cash in hand today</div>
                        <div class="text-xl font-bold text-gray-900">Rs {{ number_format($cashInHand, 2) }}</div>
                    </div>
                </div>

                @php($createValue = fn ($field, $fallback = null) => old('action_key', 'create') === 'create' ? old($field, $fallback) : $fallback)
                <form method="POST" action="{{ route('owner-transfers.store') }}" class="space-y-4" x-data="{ saving: false }" @submit="saving = true">
                    @csrf
                    <input type="hidden" name="action_key" value="create">
                    <input type="hidden" name="submission_id" value="{{ $createValue('submission_id', $submissionId) }}">
                    <x-input-error :messages="$errors->get('submission_id')" />
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="transferred_at" value="Transfer date" />
                            <x-text-input id="transferred_at" name="transferred_at" type="date" class="mt-1 block w-full" :value="$createValue('transferred_at', now()->toDateString())" :max="now()->toDateString()" required />
                            <x-input-error :messages="$errors->get('transferred_at')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="amount" value="Amount (Rs)" />
                            <x-text-input id="amount" name="amount" type="number" min="0.01" max="9999999999.99" step="0.01" class="mt-1 block w-full" :value="$createValue('amount')" required />
                            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="destination" value="Owner bank / account" />
                            <x-text-input id="destination" name="destination" type="text" maxlength="190" :value="$createValue('destination', 'Owner bank account')" class="mt-1 block w-full" />
                            <p class="mt-1 text-xs text-gray-500">Enter the bank and account name or last digits to identify the destination in the ledger.</p>
                        </div>
                        <div>
                            <x-input-label for="reference" value="Deposit reference (optional)" />
                            <x-text-input id="reference" name="reference" type="text" maxlength="120" class="mt-1 block w-full" :value="$createValue('reference')" />
                            <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="notes" value="Notes (optional)" />
                        <textarea id="notes" name="notes" maxlength="2000" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ $createValue('notes') }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </div>
                    <x-primary-button x-bind:disabled="saving" x-text="saving ? 'Saving…' : 'Record transfer'">Record transfer</x-primary-button>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="owner-transfer-list-header">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Transfers to Owner bank account</h3>
                        <p class="mt-1 text-sm text-gray-500">Review each transfer, returned cash and the remaining net amount.</p>
                    </div>
                    <div class="owner-transfer-legend" aria-label="Amount colour key">
                        <span><i class="owner-transfer-dot owner-transfer-dot-sent" aria-hidden="true"></i> Transferred</span>
                        <span><i class="owner-transfer-dot owner-transfer-dot-return" aria-hidden="true"></i> Returned to cash</span>
                    </div>
                </div>
                <div class="overflow-x-auto owner-transfer-table-frame">
                    <table class="min-w-full text-sm owner-transfer-table">
                        <thead><tr>
                            <th class="text-left">Date / reference</th>
                            <th class="text-left">Destination / notes</th>
                            <th class="text-right">Transferred (Rs)</th>
                            <th class="text-right">Returned (Rs)</th>
                            <th class="text-right">Net transferred (Rs)</th>
                            <th class="text-left">Actions</th>
                        </tr></thead>
                        @forelse($transfers as $transfer)
                            <tbody class="owner-transfer-group {{ $transfer->deleted_at ? 'owner-transfer-deleted' : '' }}">
                                <tr class="owner-transfer-main-row">
                                    <td class="owner-transfer-date-cell">
                                        <div class="owner-transfer-date">{{ $transfer->transferred_at->format('Y-m-d') }}</div>
                                        <div class="owner-transfer-reference">{{ $transfer->reference ?: 'OT-'.$transfer->id }}</div>
                                        <div class="owner-transfer-meta">{{ $transfer->creator?->name ?? '—' }}</div>
                                    </td>
                                    <td class="owner-transfer-destination-cell">
                                        <div class="owner-transfer-destination">{{ $transfer->destination }}</div>
                                        @if($transfer->notes)<div class="owner-transfer-meta">{{ $transfer->notes }}</div>@endif
                                        @if($transfer->deleted_at)
                                            <span class="owner-transfer-status owner-transfer-status-deleted">Deleted</span>
                                            <div class="owner-transfer-deletion-reason">Reason: {{ $transfer->deletion_reason }}</div>
                                            <div class="owner-transfer-meta">{{ $transfer->deleter?->name ?? '—' }} · {{ $transfer->deleted_at->format('Y-m-d H:i') }}</div>
                                        @elseif($transfer->returned_amount > 0)
                                            <span class="owner-transfer-status owner-transfer-status-returned">{{ $transfer->net_amount > 0 ? 'Partially returned' : 'Fully returned' }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right"><span class="owner-transfer-amount owner-transfer-amount-sent">{{ number_format($transfer->amount, 2) }}</span></td>
                                    <td class="text-right"><span class="owner-transfer-amount {{ $transfer->returned_amount > 0 ? 'owner-transfer-amount-return' : 'owner-transfer-amount-zero' }}">{{ number_format($transfer->returned_amount, 2) }}</span></td>
                                    <td class="text-right"><span class="owner-transfer-amount owner-transfer-amount-net">{{ number_format($transfer->net_amount, 2) }}</span></td>
                                    <td>
                                        @if(!$transfer->deleted_at)
                                            <div class="owner-transfer-actions">
                                                <button type="button" class="owner-transfer-button" data-transfer-dialog="edit-transfer-{{ $transfer->id }}">Edit</button>
                                                @if($transfer->net_amount > 0)
                                                    <button type="button" class="owner-transfer-button owner-transfer-button-return" data-transfer-dialog="return-transfer-{{ $transfer->id }}">Return amount</button>
                                                @endif
                                                <button type="button" class="owner-transfer-button owner-transfer-button-delete" data-transfer-dialog="delete-transfer-{{ $transfer->id }}">Delete</button>
                                            </div>
                                        @else
                                            <span class="owner-transfer-meta">Excluded from balances</span>
                                        @endif
                                    </td>
                                </tr>
                                @foreach($transfer->returns as $return)
                                    <tr class="owner-transfer-return-row">
                                        <td class="owner-transfer-date-cell">
                                            <div>{{ $return->returned_at->format('Y-m-d') }}</div>
                                            <div class="owner-transfer-meta">OTR-{{ $return->id }}</div>
                                        </td>
                                        <td colspan="2">
                                            <div class="owner-transfer-return-title"><span aria-hidden="true">↳</span> Return from OT-{{ $transfer->id }}</div>
                                            @if($return->notes)<div class="owner-transfer-meta">{{ $return->notes }}</div>@endif
                                            <div class="owner-transfer-meta">{{ $return->creator?->name ?? '—' }}{{ $transfer->deleted_at ? ' · Deleted with transfer' : '' }}</div>
                                        </td>
                                        <td class="text-right"><span class="owner-transfer-amount owner-transfer-amount-return">{{ number_format($return->amount, 2) }}</span></td>
                                        <td colspan="2"><span class="owner-transfer-status {{ $transfer->deleted_at ? 'owner-transfer-status-deleted' : 'owner-transfer-status-returned' }}">{{ $transfer->deleted_at ? 'Excluded from balances' : 'Cash returned to hand' }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        @empty
                            <tbody><tr><td colspan="6" class="text-gray-500">No owner transfers recorded yet.</td></tr></tbody>
                        @endforelse
                    </table>
                </div>
                @foreach($transfers as $transfer)
                    @if(!$transfer->deleted_at)
                        @include('owner-transfers.dialogs')
                    @endif
                @endforeach
                <div class="mt-4">{{ $transfers->links() }}</div>
            </div>
        </div>
    </div>
    @once
        <script src="{{ asset('js/owner-transfers.js') }}?v={{ is_file(public_path('js/owner-transfers.js')) ? filemtime(public_path('js/owner-transfers.js')) : '1' }}" defer></script>
    @endonce
</x-app-layout>
