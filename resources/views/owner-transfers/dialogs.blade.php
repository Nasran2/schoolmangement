@foreach(['edit', 'return', 'delete'] as $action)
    @php
        $actionKey = $action.'-'.$transfer->id;
        $value = fn ($field, $fallback = null) => old('action_key') === $actionKey ? old($field, $fallback) : $fallback;
    @endphp
    <dialog id="{{ $action }}-transfer-{{ $transfer->id }}" class="owner-transfer-dialog" aria-labelledby="{{ $actionKey }}-title" @if($errors->any() && old('action_key') === $actionKey) data-reopen="true" @endif>
        <div class="owner-transfer-dialog-header">
            <h3 id="{{ $actionKey }}-title">{{ ['edit' => 'Edit transfer', 'return' => 'Return amount', 'delete' => 'Delete transfer'][$action] }} · OT-{{ $transfer->id }}</h3>
            <button type="button" data-close-transfer-dialog aria-label="Close dialog" class="owner-transfer-button">×</button>
        </div>
        <p class="mt-2 text-sm text-gray-600">{{ $transfer->destination }}</p>
        @if($errors->any() && old('action_key') === $actionKey)
            <div role="alert" class="rounded-lg p-3 mt-3" style="background:#fff1f2;color:#b91c1c;">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif
        <form method="POST" action="{{ route(['edit' => 'owner-transfers.update', 'return' => 'owner-transfers.returns.store', 'delete' => 'owner-transfers.destroy'][$action], $transfer) }}" data-transfer-form>
            @csrf
            @if($action === 'edit') @method('PUT') @elseif($action === 'delete') @method('DELETE') @endif
            <input type="hidden" name="version" value="{{ $transfer->version }}">
            <input type="hidden" name="action_key" value="{{ $actionKey }}">
            @if($action === 'edit')
                <div><label for="{{ $actionKey }}-date">Transfer date</label><input id="{{ $actionKey }}-date" name="transferred_at" type="date" value="{{ $value('transferred_at', $transfer->transferred_at->toDateString()) }}" max="{{ today()->toDateString() }}" required></div>
                <div><label for="{{ $actionKey }}-amount">Transferred amount (Rs)</label><input id="{{ $actionKey }}-amount" name="amount" type="number" min="{{ max(0.01, $transfer->returned_amount) }}" max="9999999999.99" step="0.01" value="{{ $value('amount', $transfer->amount) }}" required><p class="text-xs text-gray-500 mt-1">Already returned: Rs {{ number_format($transfer->returned_amount, 2) }}</p></div>
                <div><label for="{{ $actionKey }}-destination">Owner bank / account</label><input id="{{ $actionKey }}-destination" name="destination" maxlength="190" value="{{ $value('destination', $transfer->destination) }}"></div>
                <div><label for="{{ $actionKey }}-reference">Deposit reference</label><input id="{{ $actionKey }}-reference" name="reference" maxlength="120" value="{{ $value('reference', $transfer->reference) }}"></div>
                <div><label for="{{ $actionKey }}-notes">Notes</label><textarea id="{{ $actionKey }}-notes" name="notes" maxlength="2000" rows="2">{{ $value('notes', $transfer->notes) }}</textarea></div>
            @elseif($action === 'return')
                <input type="hidden" name="submission_id" value="{{ $value('submission_id', (string) \Illuminate\Support\Str::uuid()) }}">
                <div class="rounded-lg p-3 owner-transfer-returned">Remaining transferred amount: <strong>Rs {{ number_format($transfer->net_amount, 2) }}</strong><p class="mt-1 text-sm">This return increases cash in hand and reduces the net amount transferred to the owner.</p></div>
                <div><label for="{{ $actionKey }}-amount">Amount returned to cash (Rs)</label><input id="{{ $actionKey }}-amount" name="amount" type="number" min="0.01" max="{{ $transfer->net_amount }}" step="0.01" value="{{ $value('amount') }}" required></div>
                <div><label for="{{ $actionKey }}-date">Return date</label><input id="{{ $actionKey }}-date" name="returned_at" type="date" min="{{ $transfer->transferred_at->toDateString() }}" max="{{ today()->toDateString() }}" value="{{ $value('returned_at', today()->toDateString()) }}" required></div>
                <div><label for="{{ $actionKey }}-notes">Return notes (optional)</label><textarea id="{{ $actionKey }}-notes" name="notes" maxlength="2000" rows="2">{{ $value('notes') }}</textarea></div>
            @else
                <div class="rounded-lg p-3" style="background:#fff1f2;color:#b91c1c;">The transfer and its return history will stay visible as deleted. All their cash movements will be excluded from balances. For money actually sent and returned, use Return amount.</div>
                <div><label for="{{ $actionKey }}-reason">Reason for deleting <span aria-hidden="true">*</span></label><textarea id="{{ $actionKey }}-reason" name="reason" maxlength="2000" rows="3" required>{{ $value('reason') }}</textarea></div>
            @endif
            <div class="owner-transfer-dialog-actions">
                <button type="button" data-close-transfer-dialog class="owner-transfer-button">Cancel</button>
                <x-primary-button>{{ ['edit' => 'Save changes', 'return' => 'Record return', 'delete' => 'Mark as deleted'][$action] }}</x-primary-button>
            </div>
        </form>
    </dialog>
@endforeach
