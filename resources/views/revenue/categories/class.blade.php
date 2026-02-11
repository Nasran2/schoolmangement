<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $category->name }}</h2>
                <p class="mt-1 text-sm text-gray-600">{{ $classRoom->level !== null ? ('Level '.$classRoom->level.' - ') : '' }}{{ $classRoom->name }} · Paid / Unpaid</p>
            </div>
            <a href="{{ route('revenue.categories.show', $category) }}" class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200">Back</a>
        </div>
    </x-slot>

    @php
        $cycleStart = $cycle['start'] ?? null;
        $cycleDue = $cycle['due'] ?? null;
        $amount = isset($amount) ? $amount : ($category->default_amount !== null ? (float) $category->default_amount : null);

        $paidIds = $paymentsByStudent->keys()->map(fn($k) => (int) $k)->all();
        $paidCount = count($paidIds);
        $totalCount = $students->count();
        $expectedTotal = ($amount !== null) ? ($totalCount * $amount) : null;
        $paidTotal = (float) $paymentsByStudent->sum(fn($r) => (float) ($r->total_paid ?? 0));
    @endphp

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Cycle</div>
                    <div class="mt-1 text-sm font-semibold text-gray-900">
                        @if ($cycleStart && $cycleDue)
                            {{ $cycleStart->format('d-m-Y') }} → {{ $cycleDue->format('d-m-Y') }}
                        @else
                            —
                        @endif
                    </div>
                    <div class="mt-2 text-xs text-gray-500">Reminder: {{ ($cycle['reminder'] ?? null)?->format('d-m-Y') ?? '—' }}</div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Status</div>
                    <div class="mt-1 text-lg font-bold text-gray-900">{{ $paidCount }} / {{ $totalCount }} paid</div>
                    <div class="mt-2 text-xs text-gray-500">Unpaid: {{ max(0, $totalCount - $paidCount) }}</div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Amounts</div>
                    <div class="mt-1 text-sm font-semibold text-gray-900">Paid: Rs {{ number_format($paidTotal, 2) }}</div>
                    <div class="mt-2 text-xs text-gray-500">Expected: {{ $expectedTotal !== null ? ('Rs '.number_format($expectedTotal, 2)) : '—' }}</div>
                </div>
            </div>

            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <div class="text-sm font-semibold text-gray-900">Students</div>
                    <div class="mt-1 text-xs text-gray-500">Click “Record payment” to open Add Revenue with this student + category.</div>
                </div>

                @if ($cycleStart && $cycleDue)
                    <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
                        <form method="POST" action="{{ route('revenue.categories.classes.bulkPay', ['category' => $category, 'classRoom' => $classRoom, 'due' => $cycleDue->toDateString()]) }}" class="grid grid-cols-1 sm:grid-cols-6 gap-3">
                            @csrf
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-gray-600">Paid date</label>
                                <input type="date" name="paid_at" value="{{ old('paid_at', now()->toDateString()) }}" class="mt-1 w-full rounded-lg border-gray-300" required>
                                @error('paid_at')
                                    <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-gray-600">Amount (optional)</label>
                                <input type="number" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}" placeholder="Default: {{ $amount !== null ? number_format($amount, 2) : '—' }}" class="mt-1 w-full rounded-lg border-gray-300">
                                @error('amount')
                                    <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-gray-600">Notes (optional)</label>
                                <input type="text" name="notes" value="{{ old('notes') }}" class="mt-1 w-full rounded-lg border-gray-300" placeholder="Bulk payment">
                                @error('notes')
                                    <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                                @enderror
                            </div>

                            @php
                                $pm = old('payment_method', 'cash');
                            @endphp

                            <div class="sm:col-span-6" x-data="{ pm: '{{ $pm }}' }">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <label class="flex items-start gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 cursor-pointer" :class="pm==='cash' ? 'ring-2 ring-indigo-200 border-indigo-400' : ''">
                                        <input type="radio" name="payment_method" value="cash" class="mt-1" x-model="pm">
                                        <div>
                                            <div class="text-xs font-semibold text-gray-800">Cash</div>
                                            <div class="text-[11px] text-gray-500">No extra details</div>
                                        </div>
                                    </label>
                                    <label class="flex items-start gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 cursor-pointer" :class="pm==='bank_transfer' ? 'ring-2 ring-indigo-200 border-indigo-400' : ''">
                                        <input type="radio" name="payment_method" value="bank_transfer" class="mt-1" x-model="pm">
                                        <div>
                                            <div class="text-xs font-semibold text-gray-800">Bank Transfer</div>
                                            <div class="text-[11px] text-gray-500">Ref no + Bank</div>
                                        </div>
                                    </label>
                                    <label class="flex items-start gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 cursor-pointer" :class="pm==='cheque' ? 'ring-2 ring-indigo-200 border-indigo-400' : ''">
                                        <input type="radio" name="payment_method" value="cheque" class="mt-1" x-model="pm">
                                        <div>
                                            <div class="text-xs font-semibold text-gray-800">Cheque</div>
                                            <div class="text-[11px] text-gray-500">Cheque details</div>
                                        </div>
                                    </label>
                                </div>

                                <div x-show="pm === 'bank_transfer'" x-cloak class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600">Bank</label>
                                        <input type="text" name="bank_name" value="{{ old('bank_name') }}" class="mt-1 w-full rounded-lg border-gray-300" placeholder="Bank name">
                                        @error('bank_name')
                                            <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600">Ref No (optional)</label>
                                        <input type="text" name="bank_ref_no" value="{{ old('bank_ref_no') }}" class="mt-1 w-full rounded-lg border-gray-300" placeholder="Reference">
                                        @error('bank_ref_no')
                                            <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div x-show="pm === 'cheque'" x-cloak class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600">Cheque Date</label>
                                        <input type="date" name="cheque_date" value="{{ old('cheque_date') }}" class="mt-1 w-full rounded-lg border-gray-300">
                                        @error('cheque_date')
                                            <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600">Cheque No</label>
                                        <input type="text" name="cheque_number" value="{{ old('cheque_number') }}" class="mt-1 w-full rounded-lg border-gray-300" placeholder="Cheque number">
                                        @error('cheque_number')
                                            <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600">Bank</label>
                                        <input type="text" name="cheque_bank" value="{{ old('cheque_bank') }}" class="mt-1 w-full rounded-lg border-gray-300" placeholder="Bank">
                                        @error('cheque_bank')
                                            <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="sm:col-span-6 flex items-center justify-between">
                                <div class="text-xs text-gray-600">
                                    <label class="inline-flex items-center gap-2">
                                        <input id="select-all" type="checkbox" class="rounded border-gray-300">
                                        <span>Select all unpaid</span>
                                    </label>
                                    @error('student_ids')
                                        <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                    Bulk mark as paid
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                <div class="divide-y">
                    <div class="grid grid-cols-12 px-5 py-3 text-xs font-semibold text-gray-500">
                        <div class="col-span-5">Student</div>
                        <div class="col-span-2 text-right">Expected</div>
                        <div class="col-span-3">Payment</div>
                        <div class="col-span-2 text-right">Action</div>
                    </div>

                    @foreach ($students as $s)
                        @php
                            $pay = $paymentsByStudent->get($s->id);
                            $isPaid = $pay && (float) ($pay->total_paid ?? 0) > 0;
                        @endphp
                        <div class="grid grid-cols-12 items-center px-5 py-3">
                            <div class="col-span-5">
                                <div class="text-sm font-semibold text-gray-900">{{ $s->name }}</div>
                                <div class="text-xs text-gray-500">{{ $s->admission_number }}</div>
                                @if ($cycleStart && $cycleDue)
                                    <label class="mt-2 inline-flex items-center gap-2 text-xs text-gray-600">
                                        <input type="checkbox" class="student-checkbox rounded border-gray-300" data-paid="{{ $isPaid ? '1' : '0' }}" value="{{ $s->id }}">
                                        <span>Include in bulk</span>
                                    </label>
                                @endif
                            </div>
                            <div class="col-span-2 text-right text-sm text-gray-700">
                                {{ $amount !== null ? ('Rs '.number_format($amount, 2)) : '—' }}
                            </div>
                            <div class="col-span-3">
                                @if ($cycleStart && $cycleDue)
                                    @if ($isPaid)
                                        <div class="text-sm font-semibold text-green-700">Paid</div>
                                        <div class="text-xs text-gray-500">Rs {{ number_format((float) $pay->total_paid, 2) }} · {{ \Carbon\Carbon::parse($pay->last_paid_at)->format('d-m-Y') }}</div>
                                    @else
                                        <div class="text-sm font-semibold text-red-700">Not paid</div>
                                        <div class="text-xs text-gray-500">—</div>
                                    @endif
                                @else
                                    <div class="text-sm text-gray-600">Not scheduled</div>
                                @endif
                            </div>
                            <div class="col-span-2 text-right">
                                <a href="{{ route('revenue.items.create', ['student_id' => $s->id, 'category_id' => $category->id]) }}"
                                   class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">Record payment</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const selectAll = document.getElementById('select-all');
            if (!selectAll) return;

            // Wire all checkboxes into the bulk form by cloning hidden inputs on submit
            const bulkForm = document.querySelector('form[action*="/bulk-pay"]');
            if (!bulkForm) return;

            function getStudentCheckboxes() {
                return Array.from(document.querySelectorAll('.student-checkbox'));
            }

            selectAll.addEventListener('change', () => {
                const boxes = getStudentCheckboxes();
                for (const b of boxes) {
                    const paid = b.getAttribute('data-paid') === '1';
                    if (paid) continue;
                    b.checked = selectAll.checked;
                }
            });

            bulkForm.addEventListener('submit', () => {
                bulkForm.querySelectorAll('input[name="student_ids[]"]').forEach(n => n.remove());
                const selected = getStudentCheckboxes().filter(b => b.checked).map(b => b.value);
                for (const id of selected) {
                    const inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = 'student_ids[]';
                    inp.value = id;
                    bulkForm.appendChild(inp);
                }
            });
        })();
    </script>
</x-app-layout>
