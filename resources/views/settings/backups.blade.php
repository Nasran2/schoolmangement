<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Backups') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Backup Status</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Retention: <span class="font-medium text-gray-900">{{ $retention_days }}</span> days.
                                @if($last_run_at)
                                    Last run: <span class="font-medium text-gray-900">{{ $last_run_at }}</span>
                                    @if($last_status)
                                        (<span class="font-medium {{ $last_status === 'ok' ? 'text-green-700' : 'text-red-700' }}">{{ strtoupper($last_status) }}</span>)
                                    @endif
                                @else
                                    Last run: <span class="font-medium text-gray-900">Never</span>
                                @endif
                            </p>
                            @if($last_status === 'failed' && $last_error)
                                <p class="mt-2 text-sm text-red-700">{{ $last_error }}</p>
                            @endif
                            @if($last_file)
                                <p class="mt-1 text-xs text-gray-500">Last file: {{ $last_file }}</p>
                            @endif
                        </div>

                        <div class="flex flex-col gap-3 sm:items-end">
                            <form method="POST" action="{{ route('settings.backups.update') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                @csrf
                                @method('PUT')

                                <label class="inline-flex items-center gap-2 text-sm text-gray-800">
                                    <input type="hidden" name="enabled" value="0" />
                                    <input type="checkbox" name="enabled" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ $enabled ? 'checked' : '' }} />
                                    <span>Enable backups</span>
                                </label>

                                <div class="flex items-center gap-2">
                                    <label for="retention_days" class="text-sm text-gray-700">Retention days</label>
                                    <input id="retention_days" name="retention_days" type="number" min="1" max="365" class="w-24 rounded-md border-gray-300 shadow-sm text-sm" value="{{ old('retention_days', $retention_days) }}" />
                                </div>

                                <x-primary-button>Save</x-primary-button>
                            </form>

                            <form method="POST" action="{{ route('settings.backups.run') }}" class="flex items-center gap-3">
                                @csrf
                                <x-primary-button>Run Backup Now</x-primary-button>
                            </form>
                        </div>
                    </div>

                    <div class="border-t pt-6">
                        <h3 class="text-base font-semibold text-gray-900">Available Backups</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Backups are stored securely and are only downloadable by authorized users.
                        </p>

                        @if($backups->isEmpty())
                            <div class="mt-4 rounded-md bg-gray-50 p-4 text-sm text-gray-700">
                                No backups found. Use “Run Backup Now” or set up a daily cron job.
                                <div class="mt-2 text-xs text-gray-500">Cron (recommended): run <span class="font-mono">php artisan schedule:run</span> every minute.</div>
                            </div>
                        @else
                            <div class="mt-4 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($backups as $b)
                                            <tr>
                                                <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ $b['name'] }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-700">
                                                    {{ number_format(($b['size'] ?? 0) / 1024 / 1024, 2) }} MB
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-700">{{ \Carbon\Carbon::createFromTimestamp($b['last_modified'])->toDateTimeString() }}</td>
                                                <td class="px-4 py-3 text-right text-sm">
                                                    <div class="inline-flex items-center gap-2">
                                                        <a href="{{ route('settings.backups.download', ['file' => $b['name']]) }}" class="inline-flex items-center rounded-md bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Download</a>

                                                        <form method="POST" action="{{ route('settings.backups.destroy', ['file' => $b['name']]) }}" onsubmit="return confirm('Delete this backup?')" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="inline-flex items-center rounded-md bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">Delete</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 text-xs text-gray-500">
                                Cron (recommended): run <span class="font-mono">php artisan schedule:run</span> every minute.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
