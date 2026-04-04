<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Developer Control Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <p class="font-semibold">Developer-only zone</p>
                <p class="mt-1">This is a separate dashboard only for users with the Developer role. It is not shared with Admin or Super Admin.</p>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Maintenance Mode</h3>
                            <p class="mt-1 text-sm text-gray-600">Toggle full system lock mode (custom app maintenance lock).</p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $maintenanceEnabled ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                            {{ $maintenanceEnabled ? 'ENABLED' : 'DISABLED' }}
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <form method="POST" action="{{ route('developer.maintenance.enable') }}" onsubmit="return confirm('Enable maintenance mode now?');">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Enable Maintenance</button>
                        </form>

                        <form method="POST" action="{{ route('developer.maintenance.disable') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Disable Maintenance</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Artisan Command Presets</h3>
                            <p class="mt-1 text-sm text-gray-600">Run common system operations with one click.</p>
                        </div>

                        <form method="POST" action="{{ route('developer.commands.run') }}" onsubmit="return confirm('Run full maintenance sequence now?');">
                            @csrf
                            <input type="hidden" name="action" value="run_all">
                            <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                Run Full Maintenance Sequence
                            </button>
                        </form>
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach($tools as $key => $tool)
                            <form method="POST" action="{{ route('developer.commands.run') }}" class="rounded-md border border-gray-200 p-4">
                                @csrf
                                <input type="hidden" name="action" value="{{ $key }}">

                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ $tool['label'] }}</p>
                                        <p class="mt-1 text-xs text-gray-600">{{ $tool['description'] }}</p>
                                    </div>
                                    @if($tool['dangerous'])
                                        <span class="rounded bg-red-100 px-2 py-0.5 text-[11px] font-semibold text-red-700">Danger</span>
                                    @endif
                                </div>

                                <p class="mt-3 rounded bg-gray-50 px-2 py-1 font-mono text-xs text-gray-700">php artisan {{ $tool['command'] }}</p>

                                <button type="submit" class="mt-3 inline-flex items-center rounded-md bg-gray-900 px-3 py-2 text-xs font-semibold text-white hover:bg-black">
                                    Run Command
                                </button>
                            </form>
                        @endforeach
                    </div>

                    <div class="border-t pt-6">
                        <h3 class="text-base font-semibold text-gray-900">Custom Artisan Command</h3>
                        <p class="mt-1 text-sm text-gray-600">Run any command not listed above.</p>

                        <form method="POST" action="{{ route('developer.commands.run') }}" class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
                            @csrf
                            <input type="hidden" name="action" value="custom">
                            <input
                                type="text"
                                name="custom_command"
                                value="{{ old('custom_command') }}"
                                placeholder="migrate --force"
                                class="w-full rounded-md border-gray-300 shadow-sm text-sm"
                            >
                            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                Run Custom Command
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-base font-semibold text-gray-900">System Upgrade</h3>
                    <p class="mt-1 text-sm text-gray-600">Upload a ZIP package to update system files. Protected files and runtime folders are skipped automatically.</p>

                    <form method="POST" action="{{ route('developer.upgrade') }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                        @csrf
                        <input type="file" name="upgrade_file" accept=".zip" class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100" required>
                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" onclick="return confirm('Apply this upgrade package now?');">
                            Upload and Upgrade
                        </button>
                    </form>

                    <p class="mt-2 text-xs text-gray-500">Skipped during upgrade: .env, storage/, bootstrap/cache/, node_modules/, .git/.</p>
                </div>
            </div>

            @if (!empty($results))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-base font-semibold text-gray-900">Execution Results</h3>
                        <p class="mt-1 text-sm text-gray-600">Most recent operation output.</p>

                        <div class="mt-4 space-y-4">
                            @foreach($results as $result)
                                <div class="rounded-md border border-gray-200 p-4">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $result['label'] }}</p>
                                            <p class="font-mono text-xs text-gray-600">{{ $result['command'] }}</p>
                                        </div>
                                        <span class="rounded px-2 py-1 text-xs font-semibold {{ (int) $result['exit_code'] === 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            Exit {{ $result['exit_code'] }}
                                        </span>
                                    </div>

                                    <p class="mt-2 text-xs text-gray-500">Started: {{ $result['started_at'] }} | Ended: {{ $result['ended_at'] }}</p>
                                    <pre class="mt-3 max-h-64 overflow-auto rounded-md bg-gray-900 p-3 text-xs text-gray-100">{{ $result['output'] }}</pre>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
