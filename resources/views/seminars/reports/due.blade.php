<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold">Seminar Due Payments</h1>
    </x-slot>

    <div class="py-6">
        <div class="bg-white shadow-sm rounded-lg">
            <div class="p-4 sm:p-6">
                @forelse($seminars as $seminar)
                    <div class="mb-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-sm font-semibold text-gray-800">{{ $seminar->name }}</h2>
                                <p class="text-xs text-gray-600">{{ $seminar->date?->format('Y-m-d') }}</p>
                            </div>
                            <a href="{{ route('seminars.payments', $seminar) }}" class="text-indigo-600 hover:text-indigo-800 text-sm">Open Payments</a>
                        </div>
                        <div class="mt-2 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Student</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Due Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($seminar->students as $row)
                                        <tr>
                                            <td class="px-3 py-2 text-sm">{{ $row->student?->name }}</td>
                                            <td class="px-3 py-2 text-sm">{{ number_format($row->amount ?? $seminar->fee_per_student, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="px-3 py-3 text-sm text-gray-500">No due students.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No seminars with due payments.</p>
                @endforelse
                <div class="mt-4">{{ $seminars->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
