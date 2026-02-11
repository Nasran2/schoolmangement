<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Teacher Salary — Teacher Due & Upcoming</h2>
                <div class="mt-1 text-sm text-gray-600">Month: <span class="font-semibold">{{ $monthLabel ?? '' }}</span></div>
            </div>

            <div class="flex items-center gap-2">
                <form method="GET" class="flex items-center gap-2">
                    <label class="text-sm text-gray-600">Month</label>
                    <input type="month" name="month" value="{{ $month ?? '' }}" class="border-gray-300 rounded-md shadow-sm text-sm" />
                    <button type="submit" class="inline-flex items-center px-3 py-2 bg-gray-900 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">View</button>
                </form>
                @can('teachers.salary.pay')
                    <a href="{{ route('teacher-salary-payments.create') }}" class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">Add Salary Payment</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-500">Due Teachers ({{ $monthLabel ?? '' }})</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900">{{ ($dueTeachers ?? collect())->count() }}</div>
                        @can('teachers.salary.amounts.view')
                            <div class="mt-2 text-sm font-semibold text-red-600">Rs {{ number_format($dueTotal ?? 0, 2) }}</div>
                        @else
                            <div class="mt-2 text-sm font-semibold text-gray-500">Hidden</div>
                        @endcan
                        <div class="mt-2 text-xs text-gray-600">Deadline: {{ optional($deadlineDate)->format('d M Y') }}</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-500">Paid Total ({{ $monthLabel ?? '' }})</div>
                        @can('teachers.salary.amounts.view')
                            <div class="mt-1 text-2xl font-semibold text-gray-900">Rs {{ number_format($paidTotal ?? 0, 2) }}</div>
                        @else
                            <div class="mt-1 text-2xl font-semibold text-gray-500">Hidden</div>
                        @endcan
                        <div class="mt-2 text-xs text-gray-600">Payments recorded: {{ ($payments ?? collect())->count() }}</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-500">Upcoming Expected ({{ $nextMonthLabel ?? '' }})</div>
                        @can('teachers.salary.amounts.view')
                            <div class="mt-1 text-2xl font-semibold text-gray-900">Rs {{ number_format($nextMonthTotalExpected ?? 0, 2) }}</div>
                        @else
                            <div class="mt-1 text-2xl font-semibold text-gray-500">Hidden</div>
                        @endcan
                        <div class="mt-2 text-xs text-gray-600">Based on active teachers’ salary amounts</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="text-base font-semibold text-gray-900">Due Teachers</div>
                            <div class="text-xs text-gray-600">Not paid in {{ $monthLabel ?? '' }}</div>
                        </div>
                        <div class="mt-4 divide-y rounded border">
                            @forelse(($dueTeachers ?? collect()) as $t)
                                <div class="flex items-center justify-between px-4 py-3">
                                    <div class="min-w-0">
                                        <div class="text-sm font-medium text-gray-900 truncate">{{ $t->name }}</div>
                                        @can('teachers.salary.amounts.view')
                                            <div class="text-xs text-gray-600 truncate">Salary: Rs {{ number_format($t->salary_amount ?? 0, 2) }}</div>
                                        @else
                                            <div class="text-xs text-gray-500 truncate">Salary: Hidden</div>
                                        @endcan
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @can('teachers.manage')
                                            <a href="{{ route('teachers.show', $t) }}" class="text-xs font-semibold text-gray-700 hover:text-gray-900">View</a>
                                        @endcan
                                        @can('teachers.salary.pay')
                                            <a href="{{ route('teacher-salary-payments.create', ['teacher_id' => $t->id]) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">Pay</a>
                                        @endcan
                                    </div>
                                </div>
                            @empty
                                <div class="px-4 py-3 text-sm text-gray-600">No due teachers for this month.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="text-base font-semibold text-gray-900">Paid Teachers</div>
                            <div class="text-xs text-gray-600">Paid in {{ $monthLabel ?? '' }}</div>
                        </div>
                        <div class="mt-4 divide-y rounded border">
                            @forelse(($paidTeachers ?? collect()) as $t)
                                @php($row = ($paidByTeacherId[$t->id] ?? null))
                                @php($p = is_array($row) ? ($row['payment'] ?? null) : null)
                                <div class="flex items-center justify-between px-4 py-3">
                                    <div class="min-w-0">
                                        <div class="text-sm font-medium text-gray-900 truncate">{{ $t->name }}</div>
                                        <div class="text-xs text-gray-600 truncate">
                                            @if($p)
                                                @can('teachers.salary.amounts.view')
                                                    Paid: Rs {{ number_format(($row['total_paid'] ?? 0), 2) }} · {{ optional($p->paid_at)->format('d M Y') }}
                                                @else
                                                    Paid: Hidden · {{ optional($p->paid_at)->format('d M Y') }}
                                                @endcan
                                            @else
                                                Paid
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($p)
                                            <a href="{{ route('teacher-salary-payments.show', $p) }}" class="text-xs font-semibold text-indigo-700 hover:text-indigo-900">Receipt</a>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="px-4 py-3 text-sm text-gray-600">No payments recorded for this month.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
