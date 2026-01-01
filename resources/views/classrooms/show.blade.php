<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Class Details</h2>
            <div class="flex gap-3">
                <a href="{{ route('classrooms.edit', $classroom) }}" class="text-sm text-indigo-600 hover:underline">Edit</a>
                <a href="{{ route('classrooms.index') }}" class="text-sm text-gray-600 hover:underline">Back</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12 bg-gradient-to-b from-slate-50 to-slate-100">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            {{-- Class Overview Card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ $classroom->name }}</h3>
                            <div class="mt-2 space-y-1">
                                <p class="text-sm text-gray-600">
                                    <span class="font-semibold">Level:</span> {{ $classroom->level ?? 'N/A' }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    <span class="font-semibold">Monthly Fee:</span> Rs {{ number_format($classroom->monthly_fee, 2) }}
                                </p>
                                @if($classroom->description)
                                    <p class="text-sm text-gray-600">
                                        <span class="font-semibold">Description:</span> {{ $classroom->description }}
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold {{ $classroom->active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $classroom->active ? 'Active' : 'Inactive' }}
                            </span>
                            <div class="mt-3">
                                <span class="text-3xl font-bold text-indigo-600">{{ $students->count() }}</span>
                                <p class="text-sm text-gray-600">Student{{ $students->count() !== 1 ? 's' : '' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Students Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold">Students in {{ $classroom->name }}</h3>
                    </div>

                    <div class="overflow-x-auto rounded border">
                        <table class="min-w-full divide-y">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Student</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Contact</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Address</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Status</th>
                                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600">Total Due</th>
                                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse ($students as $s)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-800">
                                            <div class="flex items-center gap-3">
                                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-700 text-xs font-semibold">{{ strtoupper(substr($s->name,0,1)) }}</span>
                                                <div>
                                                    <a class="text-indigo-600 font-medium hover:underline" href="{{ route('students.show', $s) }}">{{ $s->name }}</a>
                                                    <div class="text-xs text-gray-600">ID: {{ $s->admission_number ?? '—' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-800">{{ $s->phone ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-800">{{ $s->address ?? 'No address' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">{{ ($s->active ?? true) ? 'Active' : 'Inactive' }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm font-semibold {{ ($s->computed_due_amount ?? 0) > 0 ? 'text-red-700' : 'text-gray-900' }}">Rs {{ number_format($s->computed_due_amount ?? 0, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-sm">
                                            <div class="inline-flex items-center gap-2">
                                                <a title="View" href="{{ route('students.show', $s) }}" class="text-gray-600 hover:text-gray-900">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                </a>
                                                <a title="Edit" href="{{ route('students.edit', $s) }}" class="text-indigo-600 hover:text-indigo-800">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                    </svg>
                                                </a>
                                                @can('students.promote')
                                                    <span x-data="{ open: false, reason: '' }" class="inline">
                                                        <button title="Promote" type="button" class="text-emerald-600 hover:text-emerald-800" x-on:click="open=true">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" />
                                                            </svg>
                                                        </button>
                                                        <form x-ref="promoteForm" class="hidden" method="POST" action="{{ route('students.promote.one', $s) }}">
                                                            @csrf
                                                            <input type="hidden" name="reason" x-model="reason">
                                                        </form>
                                                        <template x-teleport="body">
                                                            <div x-cloak x-show="open">
                                                                <div class="fixed inset-0 bg-black/40 z-[100]" x-on:click="open=false"></div>
                                                                <div class="fixed inset-0 z-[101] flex items-center justify-center p-4">
                                                                    <div class="w-[90%] max-w-md rounded-lg bg-white p-5 shadow-xl">
                                                                        <div class="text-base font-semibold text-gray-800">Promote Student</div>
                                                                        <div class="mt-2 text-sm text-gray-600">Promote {{ $s->name }} to the next class?</div>
                                                                        <div class="mt-4">
                                                                            <label class="block text-sm font-medium text-gray-700">Reason (optional)</label>
                                                                            <textarea x-model="reason" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" rows="3" placeholder="Add a note or reason for this promotion..."></textarea>
                                                                        </div>
                                                                        <div class="mt-5 flex justify-end gap-2">
                                                                            <button type="button" class="rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" x-on:click="open=false; reason=''">Cancel</button>
                                                                            <button type="button" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700" x-on:click="$refs.promoteForm.submit()">Promote</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </span>
                                                @endcan
                                                @can('students.demote')
                                                    <span x-data="{ open: false, reason: '' }" class="inline">
                                                        <button title="Demote" type="button" class="text-rose-600 hover:text-rose-800" x-on:click="open=true">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
                                                            </svg>
                                                        </button>
                                                        <form x-ref="demoteForm" class="hidden" method="POST" action="{{ route('students.demote.one', $s) }}">
                                                            @csrf
                                                            <input type="hidden" name="reason" x-model="reason">
                                                        </form>
                                                        <template x-teleport="body">
                                                            <div x-cloak x-show="open">
                                                                <div class="fixed inset-0 bg-black/40 z-[100]" x-on:click="open=false"></div>
                                                                <div class="fixed inset-0 z-[101] flex items-center justify-center p-4">
                                                                    <div class="w-[90%] max-w-md rounded-lg bg-white p-5 shadow-xl">
                                                                        <div class="text-base font-semibold text-gray-800">Demote Student</div>
                                                                        <div class="mt-2 text-sm text-gray-600">Demote {{ $s->name }} to the previous class?</div>
                                                                        <div class="mt-4">
                                                                            <label class="block text-sm font-medium text-gray-700">Reason (optional)</label>
                                                                            <textarea x-model="reason" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500" rows="3" placeholder="Add a note or reason for this demotion..."></textarea>
                                                                        </div>
                                                                        <div class="mt-5 flex justify-end gap-2">
                                                                            <button type="button" class="rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" x-on:click="open=false; reason=''">Cancel</button>
                                                                            <button type="button" class="rounded-md bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700" x-on:click="$refs.demoteForm.submit()">Demote</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </span>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="px-4 py-4 text-sm text-gray-600" colspan="6">No students in this class yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
