        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-lg bg-white p-5 shadow-sm"><div class="text-sm text-gray-500">Payments recorded for selected {{ ($filters['all_years'] ?? false) ? 'installments' : $year.' installments' }}</div><div class="mt-1 text-2xl font-semibold">Rs {{ number_format($tracker['paid'], 2) }}</div></div>
            <div class="rounded-lg bg-white p-5 shadow-sm"><div class="text-sm text-gray-500">Outstanding due now</div><div class="mt-1 text-2xl font-semibold text-red-700">Rs {{ number_format($tracker['due'], 2) }}</div></div>
            <div class="rounded-lg bg-white p-5 shadow-sm"><div class="text-sm text-gray-500">Students with unpaid installments</div><div class="mt-1 text-2xl font-semibold">{{ $tracker['unpaid_students'] }}</div></div>
        </div>

        <section aria-label="Fee dues by term">
            <style>
                .class-fee-due-cards { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; }
                .class-fee-due-card { border-top: 3px solid #6366f1; min-width: 0; }
                .class-fee-due-card[data-fee="facility"] { border-top-color: #0d9488; }
                .class-fee-due-amount { overflow-wrap: anywhere; font-variant-numeric: tabular-nums; }
                @media (min-width: 640px) { .class-fee-due-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
                @media (min-width: 1024px) { .class-fee-due-cards { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
            </style>
            <div class="class-fee-due-cards">
                @foreach($dueBreakdown as $feeKey => $card)
                    <div class="class-fee-due-card rounded-lg bg-white p-5 shadow-sm" data-fee="{{ $feeKey }}">
                        <h3 class="text-sm font-semibold text-gray-700">{{ $card['label'] }}</h3>
                        <div class="class-fee-due-amount mt-1 text-2xl font-semibold {{ $card['due'] > 0 ? 'text-red-700' : 'text-gray-900' }}">Rs {{ number_format($card['due'], 2) }}</div>
                        <p class="mt-2 text-xs text-gray-500">{{ $card['selected'] ? 'Outstanding due now' : 'No selected fees in this period' }}</p>
                    </div>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-gray-500">These balances follow the filters above. Future fees are excluded until their starting date.</p>
        </section>

