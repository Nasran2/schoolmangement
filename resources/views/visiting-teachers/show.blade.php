<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ $visitingTeacher->name }}</h1>
                <p class="text-sm text-gray-500 mt-1">Visiting teacher history + class schedule overview.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('visiting-teachers.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    &larr; Back to teachers
                </a>
                <a href="{{ route('visiting-teachers.edit', $visitingTeacher) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Edit profile
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <div class="text-xs uppercase text-gray-400 tracking-wide">Contact</div>
                    <p class="text-lg font-semibold text-gray-900">{{ $visitingTeacher->phone ?: 'N/A' }}</p>
                    <p class="text-sm text-gray-500 mt-1">Phone</p>
                </div>
                <div>
                    <div class="text-xs uppercase text-gray-400 tracking-wide">Email</div>
                    <p class="text-lg font-semibold text-gray-900">{{ $visitingTeacher->email ?: 'N/A' }}</p>
                    <p class="text-sm text-gray-500 mt-1">Email</p>
                </div>
                <div>
                    <div class="text-xs uppercase text-gray-400 tracking-wide">Specialty</div>
                    <p class="text-lg font-semibold text-gray-900">{{ $visitingTeacher->specialty ?: 'General instruction' }}</p>
                    <p class="text-sm text-gray-500 mt-1">Subject focus</p>
                </div>
            </div>
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                    <div class="text-xs text-gray-500 uppercase">Extra classes taught</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $extraClasses->total() }}</div>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                    <div class="text-xs text-gray-500 uppercase">Seminars handled</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $seminars->total() }}</div>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                    <div class="text-xs text-gray-500 uppercase">Status</div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $visitingTeacher->active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-700' }}">
                        {{ $visitingTeacher->active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
        </div>

        <section class="bg-white shadow-sm rounded-xl border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">Extra class history</h2>
                    <p class="text-sm text-gray-500">All sessions this instructor has delivered, ordered by date.</p>
                </div>
                <div class="text-sm text-gray-500">
                    Showing {{ $extraClasses->firstItem() ?? 0 }} – {{ $extraClasses->lastItem() ?? 0 }} of {{ $extraClasses->total() }}
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Schedule</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Payment type</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Room</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Students</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($extraClasses as $class)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 align-middle">
                                    <div class="text-sm font-semibold text-gray-800">{{ $class->name }}</div>
                                    <div class="text-xs text-gray-400">Fee: {{ number_format($class->fee ?? $class->amount ?? 0, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 align-middle text-sm text-gray-600">
                                    {{ $class->date?->format('d M, Y') }}<br>
                                    <span class="text-xs text-gray-400">{{ $class->start_time?->format('h:i A') }} – {{ $class->end_time?->format('h:i A') }}</span>
                                </td>
                                <td class="px-6 py-4 align-middle text-sm text-gray-600 capitalize">{{ $class->payment_type }}</td>
                                <td class="px-6 py-4 align-middle text-sm text-gray-600">{{ $class->classRoom?->name ?: 'Unassigned' }}</td>
                                <td class="px-6 py-4 align-middle text-right text-sm font-semibold text-gray-900">{{ $class->students_count }}</td>
                                <td class="px-6 py-4 align-middle text-right text-sm font-medium">
                                    <a href="{{ route('extra-classes.show', $class) }}" class="text-indigo-600 hover:text-indigo-900">View class</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                                    No extra classes recorded for this teacher yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($extraClasses->hasPages())
                <div class="px-6 py-3 border-t border-gray-100 bg-gray-50">
                    {{ $extraClasses->links() }}
                </div>
            @endif
        </section>

        <section class="bg-white shadow-sm rounded-xl border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">Seminar history</h2>
                    <p class="text-sm text-gray-500">Includes hours, room and seminar fee overview.</p>
                </div>
                <div class="text-sm text-gray-500">
                    Showing {{ $seminars->firstItem() ?? 0 }} – {{ $seminars->lastItem() ?? 0 }} of {{ $seminars->total() }}
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Seminar</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Schedule</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Students</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Fee</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($seminars as $seminar)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 align-middle">
                                    <div class="text-sm font-semibold text-gray-800">{{ $seminar->name }}</div>
                                    <div class="text-xs text-gray-400">Type: {{ $seminar->notes ?: 'General seminar' }}</div>
                                </td>
                                <td class="px-6 py-4 align-middle text-sm text-gray-600">
                                    {{ $seminar->date?->format('d M, Y') }}<br>
                                    <span class="text-xs text-gray-400">{{ $seminar->start_time?->format('h:i A') }} – {{ $seminar->end_time?->format('h:i A') }}</span>
                                </td>
                                <td class="px-6 py-4 align-middle text-sm text-gray-600">{{ $seminar->primaryClassRoom?->name ?: 'Flexible' }}</td>
                                <td class="px-6 py-4 align-middle text-right text-sm font-semibold text-gray-900">{{ $seminar->students_count }}</td>
                                <td class="px-6 py-4 align-middle text-right text-sm text-gray-600">{{ number_format($seminar->fee_per_student ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                                    No seminar assignments recorded for this teacher yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($seminars->hasPages())
                <div class="px-6 py-3 border-t border-gray-100 bg-gray-50">
                    {{ $seminars->links() }}
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
