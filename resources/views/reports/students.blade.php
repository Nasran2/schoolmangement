<x-app-layout>
    <x-slot name="header">
        <div class="sm:flex sm:justify-between sm:items-center">
            <div class="mb-4 sm:mb-0">
                <h2 class="text-2xl md:text-3xl text-gray-800 font-bold">Students Report</h2>
                <div class="text-sm text-gray-500 mt-1">Complete list of students with optional filtering</div>
            </div>

            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                @can('reports.download')
                <a href="{{ request()->fullUrlWithQuery(['pdf' => 1]) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-md font-semibold text-xs text-indigo-500 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                    PDF
                </a>
                <a href="{{ request()->fullUrlWithQuery(['excel' => 1]) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-md font-semibold text-xs text-green-600 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                    Excel
                </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Filters -->
            <div class="bg-white p-5 shadow-sm sm:rounded-lg border border-gray-200 mb-6">
                <form method="GET" action="{{ route('reports.students') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label for="class_room_id" class="block text-sm font-medium text-gray-700 mb-1">Grade/Class</label>
                        <select id="class_room_id" name="class_room_id" class="form-select w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">All Classes</option>
                            @foreach($classRooms as $c)
                                <option value="{{ $c->id }}" {{ $filters['class_room_id'] == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="flex space-x-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-150">
                            Filter
                        </button>
                        <a href="{{ route('reports.students') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-50 transition ease-in-out duration-150">
                            Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-6 py-3 text-left tracking-wider">Admission No</th>
                                <th class="px-6 py-3 text-left tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left tracking-wider">Grade/Class</th>
                                <th class="px-6 py-3 text-left tracking-wider">Phone</th>
                                <th class="px-6 py-3 text-left tracking-wider">Joined Date</th>
                                <th class="px-6 py-3 text-left tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-sm">
                            @forelse($items as $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900">{{ $item->admission_number ?? $item->id }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-gray-900">{{ $item->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-gray-500">{{ $item->classRoom?->name ?? '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-gray-500">{{ $item->phone ?? '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-gray-500">{{ optional($item->joining_date)->format('M d, Y') ?? '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($item->active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        No students found matching the criteria.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($items->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $items->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
