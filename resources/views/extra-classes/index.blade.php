<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                 <h1 class="text-2xl font-bold text-gray-800">Extra Classes</h1>
                 <p class="text-sm text-gray-500 mt-1">Manage supplemental sessions and special lessons.</p>
            </div>
            
            <a href="{{ route('extra-classes.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Extra Class
            </a>
        </div>
    </x-slot>

    <div class="py-6 space-y-6">
        <!-- Search & Filter Area -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="relative w-full md:w-96">
                <!-- Search implemented as placeholder for now -->
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                 <form action="" method="GET">
                     <input type="text" name="q" class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition duration-150 ease-in-out" placeholder="Search classes..." value="{{ request('q') ?: request('search') }}">
                 </form>
            </div>
            <div class="text-sm text-gray-500">
                Showing <span class="font-medium text-gray-900">{{ $extraClasses->firstItem() ?? 0 }}</span> to <span class="font-medium text-gray-900">{{ $extraClasses->lastItem() ?? 0 }}</span> of <span class="font-medium text-gray-900">{{ $extraClasses->total() }}</span> results
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class Name</th>
                             <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fee</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Schedule</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($extraClasses as $extra)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                       <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-lg bg-pink-100 flex items-center justify-center text-pink-700">
                                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $extra->name }}</div>
                                            <!-- Teacher logic if added later -->
                                            <div class="text-xs text-gray-500">
                                                @if($extra->visitingTeacher)
                                                    {{ $extra->visitingTeacher->name }}
                                                @else
                                                    Internal
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 capitalize">
                                        {{ $extra->payment_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ number_format($extra->fee, 2) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                     <div class="text-sm text-gray-900 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        {{ $extra->date?->format('M d, Y') }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1 ml-5">
                                        {{ $extra->start_time }} {{ $extra->end_time ? '- '.$extra->end_time : '' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end items-center gap-3">
                                         <a href="{{ route('extra-classes.show', $extra) }}" class="text-indigo-600 hover:text-indigo-900" title="Overview">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        </a>

                                        <a href="{{ route('extra-classes.edit', $extra) }}" class="text-gray-500 hover:text-gray-900" title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </a>
                                        
                                        <form action="{{ route('extra-classes.destroy', $extra) }}" method="POST" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-400 hover:text-red-600" title="Delete" onclick="return confirm('Delete extra class?')">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                             <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="bg-pink-50 rounded-full p-3 mb-3">
                                            <svg class="w-8 h-8 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                        </div>
                                        <p class="font-medium text-gray-900">No extra classes found</p>
                                        <p class="text-gray-500 mt-1">Get started by creating a new supplemental class.</p>
                                        <a href="{{ route('extra-classes.create') }}" class="mt-4 text-indigo-600 hover:text-indigo-800 font-medium text-sm">Add New Class &rarr;</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
             @if($extraClasses->hasPages())
                 <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                    {{ $extraClasses->links() }}
                 </div>
            @endif
        </div>
    </div>
</x-app-layout>
