<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Teachers</h2>
                <p class="text-gray-600 text-sm mt-1">Manage teaching staff and their information</p>
            </div>
            @can('teachers.add')
                <a href="{{ route('teachers.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-semibold rounded-lg shadow-lg hover:from-blue-700 hover:to-indigo-700 transition-all">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Teacher
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm font-medium text-green-800">{{ session('status') }}</span>
                    </div>
                </div>
            @endif

            <!-- Search Card -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 mb-6">
                <form method="GET" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <label for="q" class="block text-sm font-semibold text-gray-700 mb-2">
                            <svg class="inline h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Search Teachers
                        </label>
                        <input id="q" name="q" type="text" value="{{ $filters['q'] ?? '' }}" placeholder="Search by name, phone, or classes..." class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-semibold rounded-lg shadow hover:from-blue-700 hover:to-indigo-700 transition-all">Search</button>
                        @if(isset($filters['q']) && $filters['q'])
                            <a href="{{ route('teachers.index') }}" class="px-6 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition-all">Reset</a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Teachers Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse ($teachers as $t)
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300">
                        <!-- Card Header with Gradient -->
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-6">
                            <div class="flex items-center gap-4">
                                <div class="bg-white/20 backdrop-blur-sm rounded-full p-3">
                                    <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold text-white truncate">{{ $t->name }}</h3>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-semibold text-white bg-white/20 backdrop-blur-sm rounded">
                                        {{ $t->active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-6 space-y-3">
                            <div class="flex items-center gap-3 text-sm text-gray-600">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <span>{{ $t->phone ?? 'No phone' }}</span>
                            </div>
                            
                            <div class="flex items-center gap-3 text-sm text-gray-600">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <span>{{ $t->assigned_classes ?? 'No classes' }}</span>
                            </div>

                            <div class="pt-3 border-t border-gray-100">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-gray-500 uppercase">Monthly Salary</span>
                                    <span class="text-xl font-bold text-green-600">Rs {{ number_format($t->salary_amount, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="bg-gray-50 px-6 py-4 flex items-center justify-between gap-2">
                            <a href="{{ route('teachers.show', $t) }}" class="flex-1 text-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-all">
                                View Details
                            </a>
                            @can('teachers.manage')
                                <a href="{{ route('teachers.edit', $t) }}" class="p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <span x-data="{ open:false }" class="inline-block">
                                    <button type="button" x-on:click="open=true" class="p-2 text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                    <form x-ref="delForm" class="hidden" method="POST" action="{{ route('teachers.destroy', $t) }}">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    <div x-cloak x-show="open" class="fixed inset-0 z-50 flex items-center justify-center">
                                        <div class="absolute inset-0 bg-black/40" x-on:click="open=false"></div>
                                        <div class="relative z-10 w-full max-w-sm rounded-xl bg-white p-6 shadow-2xl">
                                            <div class="flex items-center gap-3 mb-4">
                                                <div class="bg-red-100 rounded-full p-2">
                                                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                    </svg>
                                                </div>
                                                <h3 class="text-lg font-bold text-gray-900">Delete Teacher</h3>
                                            </div>
                                            <p class="text-sm text-gray-600 mb-6">Are you sure you want to delete <strong>{{ $t->name }}</strong>? This action cannot be undone.</p>
                                            <div class="flex justify-end gap-3">
                                                <button type="button" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-all" x-on:click="open=false">Cancel</button>
                                                <button type="button" class="px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition-all" x-on:click="$refs.delForm.submit()">Delete</button>
                                            </div>
                                        </div>
                                    </div>
                                </span>
                            @endcan
                        </div>
                    </div>
                @empty
                    <div class="col-span-full">
                        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-12 text-center">
                            <svg class="h-16 w-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">No Teachers Found</h3>
                            <p class="text-sm text-gray-600 mb-4">{{ isset($filters['q']) && $filters['q'] ? 'No teachers match your search criteria.' : 'Get started by adding your first teacher.' }}</p>
                            @can('teachers.add')
                                <a href="{{ route('teachers.create') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-semibold rounded-lg shadow-lg hover:from-blue-700 hover:to-indigo-700 transition-all">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Add Your First Teacher
                                </a>
                            @endcan
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($teachers->hasPages())
                <div class="mt-6">
                    {{ $teachers->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
