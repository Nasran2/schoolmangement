<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-800">Extra Classes Collection</h1>
            <a class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition" href="{{ route('reports.index') }}">← Back to Reports</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-7 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600">From</label>
                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-md border-gray-300 w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600">To</label>
                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-md border-gray-300 w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600">Class Room</label>
                        <select name="class_room_id" class="rounded-md border-gray-300 w-full">
                            <option value="">All</option>
                            @foreach($classRooms as $cr)
                                <option value="{{ $cr->id }}" @selected(($filters['class_room_id'] ?? '') == $cr->id)>{{ $cr->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600">Visiting Teacher</label>
                        <select name="visiting_teacher_id" class="rounded-md border-gray-300 w-full">
                            <option value="">All</option>
                            @foreach($visitingTeachers as $t)
                                <option value="{{ $t->id }}" @selected(($filters['visiting_teacher_id'] ?? '') == $t->id)>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600">Type</label>
                        <select name="type" class="rounded-md border-gray-300 w-full">
                            <option value="">All</option>
                            <option value="daily" @selected(($filters['type'] ?? '') === 'daily')>Daily</option>
                            <option value="monthly" @selected(($filters['type'] ?? '') === 'monthly')>Monthly</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600">Search</label>
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Class name" class="rounded-md border-gray-300 w-full" />
                    </div>
                    <div class="flex items-end">
                        <button class="px-4 py-2 bg-indigo-600 text-white rounded-md w-full">Filter</button>
                    </div>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Students</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Paid</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Due</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Collected</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Expected</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Teacher Pay</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Net</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($rows as $r)
                            <tr>
                                <td class="px-6 py-4">{{ $r->class_name }}</td>
                                <td class="px-6 py-4">{{ optional(\Carbon\Carbon::parse($r->date))->format('Y-m-d') }}</td>
                                <td class="px-6 py-4 capitalize">{{ $r->type }}</td>
                                <td class="px-6 py-4 text-right">{{ (int) $r->total }}</td>
                                <td class="px-6 py-4 text-right">{{ (int) $r->paid_count }}</td>
                                <td class="px-6 py-4 text-right">{{ (int) ($r->total - $r->paid_count) }}</td>
                                <td class="px-6 py-4 text-right font-mono">{{ number_format((float)$r->collected, 2) }}</td>
                                <td class="px-6 py-4 text-right font-mono">{{ number_format((float)$r->expected, 2) }}</td>
                                <td class="px-6 py-4 text-right font-mono">{{ number_format((float)($r->teacher_payment ?? 0), 2) }}</td>
                                <td class="px-6 py-4 text-right font-mono {{ $r->net_margin >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format((float)$r->net_margin, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">{{ $rows->links() }}</div>
        </div>
    </div>
</x-app-layout>
