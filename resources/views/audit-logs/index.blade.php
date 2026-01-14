<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Activity Logs</h2>
                <p class="text-sm text-gray-500 mt-1">Track key actions across the system with filters and search.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-4 sm:px-6 lg:px-8 py-4 border-b border-gray-200">
                <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                    <div>
                        <label class="text-xs text-gray-600">Action</label>
                        <input type="text" name="action" value="{{ $filters['action'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300" placeholder="e.g. revenue.create">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">User ID</label>
                        <input type="number" name="user_id" value="{{ $filters['user_id'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300" placeholder="e.g. 1">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">From</label>
                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">To</label>
                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Search</label>
                        <div class="mt-1 flex">
                            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="flex-1 rounded-l-md border-gray-300" placeholder="Description, IP, metadata...">
                            <button class="px-4 rounded-r-md bg-indigo-600 text-white">Filter</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="px-4 sm:px-6 lg:px-8 py-4">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Time</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">User</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Action</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Subject</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Description</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">IP</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50" x-data="{ open: false }">
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-700">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-700">{{ $log->user?->name ?? 'System' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs font-mono text-indigo-700">{{ $log->action }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-700">
                                    @if($log->auditable)
                                        {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-700">
                                    <div>{{ $log->formatted_description ?? $log->description }}</div>
                                    @if($log->formatted_metadata)
                                        <div class="mt-1 text-xs text-gray-600">{{ $log->formatted_metadata }}</div>
                                    @endif
                                    @can('audit_logs.view')
                                        @if($log->metadata)
                                            <div class="mt-2">
                                                <button type="button" @click="open = !open" class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded bg-gray-100 text-gray-800 hover:bg-gray-200">
                                                    <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.29a.75.75 0 01-.02-1.08z" clip-rule="evenodd"/></svg>
                                                    <span x-text="open ? 'Hide details' : 'Show details'"></span>
                                                </button>
                                            </div>
                                            <div class="mt-2" x-show="open" x-cloak>
                                                <pre class="text-xs text-gray-600 bg-gray-50 p-2 rounded">{{ json_encode($log->metadata, JSON_PRETTY_PRINT) }}</pre>
                                                @if(!empty($log->user_agent))
                                                    <div class="mt-1 text-xs text-gray-500">Agent: {{ $log->user_agent }}</div>
                                                @endif
                                            </div>
                                        @endif
                                    @endcan
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-600">{{ $log->ip_address }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-6 text-center text-sm text-gray-600">No logs found for the selected filters.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $logs->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
