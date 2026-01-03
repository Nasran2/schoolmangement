<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Student Due Aging</h2>
                <p class="text-gray-600 text-sm mt-1">Students grouped by unpaid months (aging buckets)</p>
            </div>
            <a class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition" href="{{ route('reports.index') }}">← Back to Reports</a>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-lg border border-gray-100 mb-8">
                <div class="border-b border-gray-200 px-6 py-6 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <h3 class="text-lg font-semibold text-gray-800">Filters</h3>
                </div>
                <div class="p-8">
                    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-6">
                        <div>
                            <x-input-label for="class_room_id" :value="__('Class Room')" class="font-semibold mb-2" />
                            <select id="class_room_id" name="class_room_id" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm">
                                <option value="">All</option>
                                @foreach($classRooms as $cr)
                                    <option value="{{ $cr->id }}" @selected(($filters['class_room_id'] ?? '') == $cr->id)>{{ $cr->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 select-none">
                                <input type="checkbox" name="only_active" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked(($filters['only_active'] ?? '1') === '1')>
                                <span class="text-sm font-semibold text-gray-700">Only Active Students</span>
                            </label>
                        </div>
                        <div class="flex items-end gap-2 lg:col-span-4">
                            <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-sm">Filter</button>
                            @can('reports.download')
                                <a class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition shadow-sm" href="{{ route('reports.student_due_aging', array_merge(request()->query(), ['pdf' => 1])) }}">PDF</a>
                                <a class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition shadow-sm" href="{{ route('reports.student_due_aging', array_merge(request()->query(), ['download' => 1])) }}">CSV</a>
                            @endcan
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg border border-gray-100 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Aging Buckets</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bucket</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Students</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Due</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($buckets as $b)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $b['label'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ (int) $b['students'] }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-red-700 text-right">Rs {{ number_format((float) $b['due'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bucket</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unpaid Months</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Due</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($rows as $r)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <a class="text-indigo-600 hover:underline" href="{{ route('students.show', $r['student']) }}">{{ $r['student']->name }}</a>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $r['class_room']?->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $r['bucket'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ (int) $r['unpaid_months'] }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-red-700 text-right">Rs {{ number_format((float) $r['due'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">No students with due found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
