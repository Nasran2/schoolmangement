<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Revenue Reminders</h2>
                <p class="mt-1 text-sm text-gray-600">Upcoming reminders in the next {{ $days }} days</p>
            </div>
            <form method="GET" class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Days</label>
                <input type="number" name="days" min="1" max="60" value="{{ $days }}" class="w-24 rounded-lg border-gray-300">
                <button class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Filter</button>
            </form>
        </div>
        <div class="mt-2 text-xs text-gray-500">Range: {{ $start->format('d-m-Y') }} → {{ $end->format('d-m-Y') }}</div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
                <div class="grid grid-cols-12 px-5 py-3 text-xs font-semibold text-gray-500 border-b">
                    <div class="col-span-4">Category</div>
                    <div class="col-span-2">Reminder</div>
                    <div class="col-span-2">Due</div>
                    <div class="col-span-2 text-right">Paid</div>
                    <div class="col-span-2 text-right">Expected</div>
                </div>

                @forelse ($rows as $r)
                    @php
                        $cat = $r['category'];
                        $rem = $r['reminder'];
                        $due = $r['due'];
                        $paidStudents = (int) ($r['paid_students'] ?? 0);
                        $totalStudents = (int) ($r['total_students'] ?? 0);
                        $expected = (float) ($r['expected_amount'] ?? 0);
                        $paidAmt = (float) ($r['paid_amount'] ?? 0);
                        $overdue = (bool) ($r['is_overdue_reminder'] ?? false);
                    @endphp
                    <a href="{{ route('revenue.categories.show', ['category' => $cat, 'due' => $due->toDateString()]) }}"
                       class="grid grid-cols-12 items-center px-5 py-3 hover:bg-gray-50">
                        <div class="col-span-4">
                            <div class="text-sm font-semibold text-gray-900">{{ $cat->name }}</div>
                            <div class="text-xs text-gray-500">{{ $paidStudents }} / {{ $totalStudents }} students paid</div>
                        </div>
                        <div class="col-span-2 text-sm {{ $overdue ? 'text-red-700 font-semibold' : 'text-gray-700' }}">
                            {{ $rem->format('d-m-Y') }}
                            @if ($overdue)
                                <div class="text-[11px] text-red-600">Overdue</div>
                            @endif
                        </div>
                        <div class="col-span-2 text-sm text-gray-700">{{ $due->format('d-m-Y') }}</div>
                        <div class="col-span-2 text-right text-sm text-gray-700">Rs {{ number_format($paidAmt, 2) }}</div>
                        <div class="col-span-2 text-right text-sm text-gray-700">Rs {{ number_format($expected, 2) }}</div>
                    </a>
                @empty
                    <div class="px-5 py-6 text-sm text-gray-600">No reminders in this range.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
