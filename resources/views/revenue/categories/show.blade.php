<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $category->name }}</h2>
                <p class="mt-1 text-sm text-gray-600">Category collection overview</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('revenue.categories.edit', $category) }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-indigo-700 ring-1 ring-indigo-200 hover:bg-indigo-50">Edit</a>
                <a href="{{ route('revenue.categories.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200">Back</a>
            </div>
        </div>
    </x-slot>

    @php
        $interval = $category->intervalMonths();
        $defaultAmount = $category->default_amount !== null ? (float) $category->default_amount : null;

        $type = (string) $category->payment_type;
        $label = match ($type) {
            'monthly' => 'Monthly',
            '2_months' => 'Every 2 Months',
            '3_months' => 'Every 3 Months',
            '6_months' => 'Every 6 Months',
            'yearly' => 'Yearly',
            'custom_months' => $interval ? ('Every '.$interval.' Months') : 'Custom',
            'one_time' => 'One-time',
            default => $type,
        };

        $cycleStart = $cycle['start'] ?? null;
        $cycleDue = $cycle['due'] ?? null;
        $reminderDate = $cycle['reminder'] ?? null;
    @endphp

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Payment Type</div>
                    <div class="mt-1 text-lg font-bold text-gray-900">{{ $label }}</div>
                    <div class="mt-2 text-sm text-gray-600">Applies: {{ $category->applies_to_all ? 'All classes' : 'Selected classes' }}</div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Next Payment</div>
                    @if ($cycleDue)
                        <div class="mt-1 text-lg font-bold text-gray-900">{{ $cycleDue->format('d-m-Y') }}</div>
                        <div class="mt-2 text-sm text-gray-600">Reminder: {{ $reminderDate?->format('d-m-Y') }}</div>
                    @else
                        <div class="mt-1 text-lg font-bold text-gray-900">—</div>
                        <div class="mt-2 text-sm text-gray-600">Not scheduled (one-time)</div>
                    @endif
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Amount / Student</div>
                    <div class="mt-1 text-lg font-bold text-gray-900">{{ $defaultAmount !== null ? ('Rs '.number_format($defaultAmount, 2)) : '—' }}</div>
                    <div class="mt-2 text-sm text-gray-600">Reminder lead: {{ (int) ($category->reminder_days_before ?? 5) }} days</div>
                </div>
            </div>

            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <div class="text-sm font-semibold text-gray-900">Collections by class</div>
                    @if ($cycleStart && $cycleDue)
                        <div class="mt-1 text-xs text-gray-500">Current cycle: {{ $cycleStart->format('d-m-Y') }} → {{ $cycleDue->format('d-m-Y') }}</div>
                    @endif
                </div>

                <div class="divide-y">
                    <div class="grid grid-cols-12 px-5 py-3 text-xs font-semibold text-gray-500">
                        <div class="col-span-4">Class</div>
                        <div class="col-span-2 text-right">Students</div>
                        <div class="col-span-3 text-right">Expected</div>
                        <div class="col-span-3 text-right">Paid</div>
                    </div>

                    @forelse ($classRooms as $cr)
                        @php
                            $total = (int) ($studentCounts[$cr->id] ?? 0);
                            $paid = (int) ($paidCounts[$cr->id] ?? 0);
                            $amt = $amountOverrides[$cr->id] ?? $defaultAmount;
                            $expected = ($amt !== null) ? ($total * $amt) : null;
                            $paidAmt = $paidAmounts[$cr->id] ?? null;
                        @endphp
                        <a href="{{ route('revenue.categories.classes.show', ['category' => $category, 'classRoom' => $cr, 'due' => $cycleDue?->toDateString()]) }}" class="grid grid-cols-12 items-center px-5 py-3 hover:bg-gray-50">
                            <div class="col-span-4 text-sm font-semibold text-gray-900">
                                {{ $cr->level !== null ? ('Level '.$cr->level.' - ') : '' }}{{ $cr->name }}
                            </div>
                            <div class="col-span-2 text-right text-sm text-gray-700">{{ $total }}</div>
                            <div class="col-span-3 text-right text-sm text-gray-700">{{ $expected !== null ? ('Rs '.number_format($expected, 2)) : '—' }}</div>
                            <div class="col-span-3 text-right text-sm text-gray-700">
                                @if ($cycleDue)
                                    {{ $paid }} / {{ $total }}
                                    @if ($paidAmt !== null)
                                        <span class="text-xs text-gray-500">(Rs {{ number_format((float) $paidAmt, 2) }})</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="px-5 py-4 text-sm text-gray-600">No classes found.</div>
                    @endforelse
                </div>
            </div>

            @if (!empty($history))
                <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <div class="text-sm font-semibold text-gray-900">Cycle History</div>
                        <div class="mt-1 text-xs text-gray-500">Shows snapshot based on current class assignments.</div>
                    </div>
                    <div class="divide-y">
                        <div class="grid grid-cols-12 px-5 py-3 text-xs font-semibold text-gray-500">
                            <div class="col-span-4">Due date</div>
                            <div class="col-span-3 text-right">Students paid</div>
                            <div class="col-span-3 text-right">Paid amount</div>
                            <div class="col-span-2 text-right">View</div>
                        </div>
                        @foreach ($history as $h)
                            @php
                                $d = $h['cycle']['due'] ?? null;
                            @endphp
                            <div class="grid grid-cols-12 items-center px-5 py-3">
                                <div class="col-span-4 text-sm font-semibold text-gray-900">
                                    {{ $d ? $d->format('d-m-Y') : '—' }}
                                    <div class="text-xs text-gray-500">Reminder: {{ ($h['cycle']['reminder'] ?? null)?->format('d-m-Y') }}</div>
                                </div>
                                <div class="col-span-3 text-right text-sm text-gray-700">
                                    {{ (int) ($h['paid_students'] ?? 0) }} / {{ (int) ($h['total_students'] ?? 0) }}
                                </div>
                                <div class="col-span-3 text-right text-sm text-gray-700">
                                    Rs {{ number_format((float) ($h['paid_amount'] ?? 0), 2) }}
                                    <div class="text-xs text-gray-500">Expected: Rs {{ number_format((float) ($h['expected_amount'] ?? 0), 2) }}</div>
                                </div>
                                <div class="col-span-2 text-right">
                                    @if ($d)
                                        <a class="text-sm text-indigo-600 hover:underline" href="{{ route('revenue.categories.show', [$category, 'due' => $d->toDateString()]) }}">Open</a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
