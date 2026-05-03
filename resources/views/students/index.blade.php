<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-gray-900 leading-tight">Students</h2>
                <p class="mt-1 text-sm text-gray-600">Manage student information and records</p>
            </div>
            @can('students.add')
                <a href="{{ route('students.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-indigo-500 border border-transparent rounded-lg font-semibold text-sm text-white shadow-lg hover:from-indigo-700 hover:to-indigo-600 transition-all duration-150">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add Student
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-b from-slate-50 to-white">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                @php $statusType = session('status_type', 'success'); @endphp
                <div class="mb-4 rounded-lg p-4 text-sm shadow-sm {{ $statusType === 'warning' ? 'bg-amber-50 border border-amber-200 text-amber-900' : 'bg-green-50 border border-green-200 text-green-800' }}">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Statistics Cards --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Students</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900">{{ $totalStudents ?? $students->total() }}</p>
                        </div>
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-white">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Active Students</p>
                            <p class="mt-2 text-3xl font-bold text-emerald-600">{{ $activeStudents ?? $students->where('active', true)->count() }}</p>
                        </div>
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-emerald-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-white">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Students with Due</p>
                            <p class="mt-2 text-3xl font-bold text-rose-600">{{ $studentsWithDueCount ?? $students->filter(fn($s) => ($s->computed_due_amount ?? $s->due_amount) > 0)->count() }}</p>
                        </div>
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-rose-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-white">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Due (Receivable)</p>
                            <p class="mt-2 text-3xl font-bold text-rose-600">Rs {{ number_format((float) ($totalReceivableDue ?? 0), 2) }}</p>
                            <p class="text-xs text-gray-500 mt-1">All students with due</p>
                        </div>
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-rose-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-white">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100">
                <div class="p-6">
                    {{-- Search and Actions Bar --}}
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                        <form method="GET" class="flex-1 flex flex-col sm:flex-row gap-3">
                            <div class="flex-1 relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                    </svg>
                                </div>
                                <input id="q" name="q" type="text" class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ $filters['q'] ?? '' }}" placeholder="Search by name, admission no, phone, or class..." />
                            </div>
                            <div class="sm:w-64">
                                @php $status = $filters['status'] ?? 'all'; @endphp
                                <select name="status" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="all" {{ $status==='all' ? 'selected' : '' }}>All</option>
                                    <option value="active" {{ $status==='active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ $status==='inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="alumni" {{ $status==='alumni' ? 'selected' : '' }}>Alumni</option>
                                </select>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-sm text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                                    Search
                                </button>
                                <a href="{{ route('students.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-sm text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                                    Reset
                                </a>
                            </div>
                        </form>

                        @canany(['students.promote','students.demote'])
                            <div class="flex items-center gap-2">
                                @can('students.promote')
                                    <form method="POST" action="{{ route('students.promote') }}" onsubmit="return confirm('Promote all students by one level?')">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-2 px-3 py-2 bg-emerald-600 border border-transparent rounded-lg font-semibold text-sm text-white shadow-sm hover:bg-emerald-700 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" />
                                            </svg>
                                            Promote All
                                        </button>
                                    </form>
                                @endcan
                                @can('students.demote')
                                    <form method="POST" action="{{ route('students.demote') }}" onsubmit="return confirm('Demote all students by one level?')">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-2 px-3 py-2 bg-rose-600 border border-transparent rounded-lg font-semibold text-sm text-white shadow-sm hover:bg-rose-700 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
                                            </svg>
                                            Demote All
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        @endcanany
                    </div>

                    {{-- Students Table --}}
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Student</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Contact</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Address</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">Total Due</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($students as $s)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white text-sm font-bold shadow-md">
                                                        {{ strtoupper(substr($s->name,0,1)) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <a class="text-sm font-semibold text-gray-900 hover:text-indigo-600 transition-colors" href="{{ route('students.show', $s) }}">{{ $s->name }}</a>
                                                    <div class="text-xs text-gray-500">ID: {{ $s->admission_number ?? '—' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $s->phone ?? 'N/A' }}</div>
                                            <div class="text-xs text-gray-500">{{ $s->alumni ? 'Alumni' : ($s->classRoom?->name ?? ($s->class ?? '—')) }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            <div class="max-w-xs truncate">{{ $s->address ?? 'No address' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $label = $s->alumni ? 'Alumni' : (($s->active ?? true) ? 'Active' : 'Inactive');
                                                $cls = $s->alumni ? 'bg-amber-100 text-amber-800' : (($s->active ?? true) ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-800');
                                            @endphp
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cls }}">
                                                {{ $label }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <div class="text-sm font-bold {{ ($s->computed_due_amount ?? $s->due_amount) > 0 ? 'text-rose-600' : 'text-gray-900' }}">
                                                Rs {{ number_format($s->computed_due_amount ?? $s->due_amount, 2) }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm">
                                            <div class="inline-flex items-center gap-2">
                                                <a title="View" href="{{ route('students.show', $s) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-2 text-indigo-700 ring-1 ring-inset ring-indigo-200 hover:bg-indigo-100 hover:text-indigo-800 transition-colors">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    <span class="text-xs font-semibold">View</span>
                                                </a>
                                                @can('students.manage')
                                                    <a title="Edit" href="{{ route('students.edit', $s) }}" class="text-indigo-600 hover:text-indigo-800">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                        </svg>
                                                    </a>
                                                @endcan
                                                @can('students.delete')
                                                    <span x-data="{ open:false }" class="inline">
                                                        <button title="Delete" type="button" class="text-red-600 hover:text-red-800" x-on:click="open=true">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                            </svg>
                                                        </button>
                                                        <form x-ref="delForm" class="hidden" method="POST" action="{{ route('students.destroy', $s) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>
                                                        <div x-cloak x-show="open" class="fixed inset-0 z-50 flex items-center justify-center">
                                                            <div class="absolute inset-0 bg-black/40" x-on:click="open=false"></div>
                                                            <div class="relative z-10 w-full max-w-sm rounded-md bg-white p-5 shadow-lg">
                                                                <div class="text-sm font-semibold text-gray-800">Delete Student</div>
                                                                <div class="mt-2 text-sm text-gray-600">Are you sure you want to delete this student?</div>
                                                                <div class="mt-4 flex justify-end gap-2">
                                                                    <button type="button" class="rounded-md border px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50" x-on:click="open=false">Cancel</button>
                                                                    <button type="button" class="rounded-md bg-red-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-red-700" x-on:click="$refs.delForm.submit()">Delete</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </span>
                                                @endcan
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
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mx-auto h-12 w-12 text-gray-400">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                            </svg>
                                            <h3 class="mt-2 text-sm font-semibold text-gray-900">No students found</h3>
                                            <p class="mt-1 text-sm text-gray-500">Get started by adding a new student.</p>
                                            <div class="mt-6">
                                                <a href="{{ route('students.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-sm text-white shadow-sm hover:bg-indigo-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                                    </svg>
                                                    Add Student
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $students->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
