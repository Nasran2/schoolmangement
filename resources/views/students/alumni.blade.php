<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-gray-900 leading-tight">Alumni</h2>
                <p class="mt-1 text-sm text-gray-600">List of retired/alumni students. Export CSV or manage leaving documents.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ request()->fullUrlWithQuery(['download' => 1]) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-800 text-sm font-semibold rounded-lg border border-gray-200 hover:bg-gray-50 transition-all">
                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 2h9l5 5v15a2 2 0 01-2 2H6a2 2 0 01-2-2V4a2 2 0 012-2z" />
                    </svg>
                    Export CSV
                </a>
                <a href="{{ route('students.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition-all">
                    Back to Students
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-b from-slate-50 to-white">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-800 shadow-sm">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100">
                <div class="p-6">
                    <form method="GET" class="flex flex-col sm:flex-row gap-3 mb-4">
                        <div class="flex-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                </svg>
                            </div>
                            <input id="q" name="q" type="text" class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ $filters['q'] ?? '' }}" placeholder="Search by name, admission no, phone, or class..." />
                        </div>
                        <div class="sm:w-64">
                            @php $ld = $filters['leaving_docs'] ?? 'all'; @endphp
                            <select name="leaving_docs" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="all" {{ $ld==='all' ? 'selected' : '' }}>Leaving Docs: All</option>
                                <option value="issued" {{ $ld==='issued' ? 'selected' : '' }}>Leaving Docs: Issued</option>
                                <option value="not_issued" {{ $ld==='not_issued' ? 'selected' : '' }}>Leaving Docs: Not Issued</option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-sm text-white shadow-sm hover:bg-indigo-700 transition-colors">Search</button>
                            <a href="{{ route('students.alumni') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-sm text-gray-700 shadow-sm hover:bg-gray-50 transition-colors">Reset</a>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('students.alumni.leaving_docs') }}" x-data="{ all:false }" class="space-y-3">
                        @csrf
                        <div class="flex flex-wrap items-center gap-3">
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" x-model="all" @change="document.querySelectorAll('.row-check').forEach(cb => cb.checked = all)" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Select all on page</span>
                            </label>

                            <input type="hidden" name="value" id="bulk_value" value="1">
                            <button type="submit" onclick="document.getElementById('bulk_value').value='1'" class="inline-flex items-center gap-2 px-3 py-2 bg-emerald-600 text-white text-xs font-semibold rounded-md hover:bg-emerald-700">
                                Mark Leaving Docs Issued
                            </button>
                            <button type="submit" onclick="document.getElementById('bulk_value').value='0'" class="inline-flex items-center gap-2 px-3 py-2 bg-amber-600 text-white text-xs font-semibold rounded-md hover:bg-amber-700">
                                Mark Not Issued
                            </button>
                        </div>

                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Select</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Student</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Contact</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Class</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Joining Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Leaving Docs</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse ($students as $s)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3">
                                                <input type="checkbox" name="ids[]" value="{{ $s->id }}" class="row-check rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-amber-500 text-white text-sm font-bold shadow-md">{{ strtoupper(substr($s->name,0,1)) }}</span>
                                                    </div>
                                                    <div>
                                                        <a class="text-sm font-semibold text-gray-900 hover:text-indigo-600 transition-colors" href="{{ route('students.show', $s) }}">{{ $s->name }}</a>
                                                        <div class="text-xs text-gray-500">ID: {{ $s->admission_number ?? '—' }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $s->phone ?? 'N/A' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $s->classRoom?->name ?? ($s->class ?? '—') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ optional($s->joining_date)->format('d-m-Y') ?? '—' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($s->leaving_docs_issued)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">Issued</span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Not Issued</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                                <a href="{{ route('students.show', $s) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-2 text-indigo-700 ring-1 ring-inset ring-indigo-200 hover:bg-indigo-100 hover:text-indigo-800 transition-colors">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    <span class="text-xs font-semibold">View</span>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-600">No alumni found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">{{ $students->links() }}</div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
