<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Student Due Amount Report</h2>
                <p class="text-gray-600 text-sm mt-1">Monthly fee due amounts based on fee start date and payments</p>
            </div>
            <a class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition" href="{{ route('reports.index') }}">← Back to Reports</a>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-lg border border-gray-100 mb-8">
                <div class="border-b border-gray-200 px-6 py-6 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <h3 class="text-lg font-semibold text-gray-800">Filter Students</h3>
                    <p class="text-sm text-gray-600 mt-1">Filter by class and active status</p>
                </div>

                <div class="p-8">
                    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-6">
                        <div class="sm:col-span-2">
                            <x-input-label for="q" :value="__('Search Student')" class="font-semibold mb-2" />
                            <x-text-input id="q" name="q" type="text" class="mt-1 block w-full" placeholder="Name, admission no, phone" :value="$filters['q'] ?? ''" />
                        </div>

                        <div>
                            <x-input-label for="class_room_id" :value="__('Class Room')" class="font-semibold mb-2" />
                            <select id="class_room_id" name="class_room_id" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm">
                                <option value="">All Classes</option>
                                @foreach($classRooms as $room)
                                    <option value="{{ $room->id }}" @selected(($filters['class_room_id'] ?? '') == $room->id)>{{ $room->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="min_due" :value="__('Min Due (Rs)')" class="font-semibold mb-2" />
                            <x-text-input id="min_due" name="min_due" type="number" step="0.01" class="mt-1 block w-full" :value="$filters['min_due'] ?? ''" />
                        </div>

                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2">
                                <input type="hidden" name="only_active" value="0" />
                                <input type="checkbox" name="only_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ ($filters['only_active'] ?? '1') == '1' ? 'checked' : '' }} />
                                <span class="text-sm text-gray-800">Only active students</span>
                            </label>
                        </div>

                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2">
                                <input type="hidden" name="only_with_due" value="0" />
                                <input type="checkbox" name="only_with_due" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ ($filters['only_with_due'] ?? '1') == '1' ? 'checked' : '' }} />
                                <span class="text-sm text-gray-800">Only students with due</span>
                            </label>
                        </div>

                        <div class="flex items-end gap-2 lg:col-span-2">
                            <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-sm">Filter</button>

                            @can('reports.download')
                                <a class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition shadow-sm"
                                   href="{{ route('reports.student_due', array_merge(request()->query(), ['pdf' => 1])) }}">PDF</a>
                                <a class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition shadow-sm"
                                   href="{{ route('reports.student_due', array_merge(request()->query(), ['download' => 1])) }}">CSV</a>
                            @endcan
                        </div>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <p class="text-sm text-gray-600 font-medium">Students (filtered)</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $items->total() }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <p class="text-sm text-gray-600 font-medium">Students With Due</p>
                    <p class="text-3xl font-bold text-indigo-600 mt-2">{{ $totalStudentsWithDue }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-6">
                    <p class="text-sm text-gray-600 font-medium">Total Due</p>
                    <p class="text-3xl font-bold text-red-600 mt-2">Rs {{ number_format($totalDue, 2) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Admission No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monthly Fee</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Months Due</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Paid</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Due</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($items as $row)
                                @php($s = $row['student'])
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $s->admission_number }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $s->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $row['class_room']?->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">Rs {{ number_format((float) $row['monthly_fee'], 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ (int) $row['months_due'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">Rs {{ number_format((float) $row['paid'], 2) }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-red-700 text-right">Rs {{ number_format((float) $row['due'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">No students found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-6">{{ $items->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
