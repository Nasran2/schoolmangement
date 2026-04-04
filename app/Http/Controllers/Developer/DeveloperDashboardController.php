<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class DeveloperDashboardController extends Controller
{
    /**
     * @return array<string, array{label:string,command:string,description:string,dangerous:bool}>
     */
    private function commandMap(): array
    {
        return [
            'optimize_clear' => [
                'label' => 'Optimize Clear',
                'command' => 'optimize:clear',
                'description' => 'Clear config, route, view, and event caches.',
                'dangerous' => false,
            ],
            'cache_clear' => [
                'label' => 'Cache Clear',
                'command' => 'cache:clear',
                'description' => 'Clear application cache store.',
                'dangerous' => false,
            ],
            'config_cache' => [
                'label' => 'Config Cache',
                'command' => 'config:cache',
                'description' => 'Rebuild cached configuration file.',
                'dangerous' => false,
            ],
            'route_cache' => [
                'label' => 'Route Cache',
                'command' => 'route:cache',
                'description' => 'Rebuild route cache for faster bootstrap.',
                'dangerous' => false,
            ],
            'view_cache' => [
                'label' => 'View Cache',
                'command' => 'view:cache',
                'description' => 'Compile and cache all Blade templates.',
                'dangerous' => false,
            ],
            'event_cache' => [
                'label' => 'Event Cache',
                'command' => 'event:cache',
                'description' => 'Cache discovered events/listeners.',
                'dangerous' => false,
            ],
            'storage_link' => [
                'label' => 'Storage Link',
                'command' => 'storage:link',
                'description' => 'Create public/storage symlink if missing.',
                'dangerous' => false,
            ],
            'migrate' => [
                'label' => 'Run Migrations',
                'command' => 'migrate --force',
                'description' => 'Apply pending database migrations.',
                'dangerous' => true,
            ],
            'migrate_status' => [
                'label' => 'Migration Status',
                'command' => 'migrate:status',
                'description' => 'Show migration status table.',
                'dangerous' => false,
            ],
            'queue_restart' => [
                'label' => 'Queue Restart',
                'command' => 'queue:restart',
                'description' => 'Gracefully restart queue workers.',
                'dangerous' => false,
            ],
            'schedule_run' => [
                'label' => 'Schedule Run',
                'command' => 'schedule:run',
                'description' => 'Run due scheduled tasks once.',
                'dangerous' => false,
            ],
            'about' => [
                'label' => 'About',
                'command' => 'about',
                'description' => 'Show framework and environment details.',
                'dangerous' => false,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function maintenanceSequence(): array
    {
        return [
            'optimize_clear',
            'storage_link',
            'migrate',
            'config_cache',
            'route_cache',
            'view_cache',
            'event_cache',
        ];
    }

    public function index(Request $request): View
    {
        return view('developer.dashboard', [
            'tools' => $this->commandMap(),
            'maintenanceSequence' => $this->maintenanceSequence(),
            'maintenanceEnabled' => (string) app('settings')->get('system.lock.enabled', '0') === '1',
            'results' => $request->session()->get('developer_dashboard.results', []),
        ]);
    }

    public function runCommand(Request $request): RedirectResponse
    {
        $tools = $this->commandMap();
        $allowedActions = array_merge(array_keys($tools), ['run_all', 'custom']);

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:'.implode(',', $allowedActions)],
            'custom_command' => ['nullable', 'string', 'max:500'],
        ]);

        $action = (string) $validated['action'];
        $results = [];

        if ($action === 'run_all') {
            foreach ($this->maintenanceSequence() as $key) {
                $results[] = $this->executeCommand($tools[$key]['command'], $tools[$key]['label']);
            }
        } elseif ($action === 'custom') {
            $custom = trim((string) ($validated['custom_command'] ?? ''));
            $custom = (string) preg_replace('/^\s*php\s+artisan\s+/i', '', $custom);

            if ($custom === '') {
                return back()->withErrors([
                    'custom_command' => 'Please enter an artisan command.',
                ]);
            }

            $results[] = $this->executeCommand($custom, 'Custom Command');
        } else {
            $tool = $tools[$action];
            $results[] = $this->executeCommand($tool['command'], $tool['label']);
        }

        app(AuditLogger::class)->log(
            'developer.dashboard.command.run',
            null,
            'Developer command executed',
            [
                'action' => $action,
                'count' => count($results),
                'failed' => count(array_filter($results, fn ($r) => $r['exit_code'] !== 0)),
                'commands' => array_map(fn ($r) => $r['command'], $results),
            ]
        );

        $status = count(array_filter($results, fn ($r) => $r['exit_code'] !== 0)) === 0
            ? 'Command(s) executed successfully.'
            : 'Some command(s) failed. Check output below.';

        return redirect()
            ->route('developer.dashboard')
            ->with('status', $status)
            ->with('developer_dashboard.results', $results);
    }

    public function enableMaintenance(): RedirectResponse
    {
        app('settings')->set('system.lock.enabled', '1', 'system');

        app(AuditLogger::class)->log(
            'developer.dashboard.maintenance.enable',
            null,
            'Maintenance mode enabled by developer'
        );

        return redirect()
            ->route('developer.dashboard')
            ->with('status', 'Maintenance mode enabled.');
    }

    public function disableMaintenance(): RedirectResponse
    {
        app('settings')->set('system.lock.enabled', '0', 'system');

        app(AuditLogger::class)->log(
            'developer.dashboard.maintenance.disable',
            null,
            'Maintenance mode disabled by developer'
        );

        return redirect()
            ->route('developer.dashboard')
            ->with('status', 'Maintenance mode disabled.');
    }

    public function upgrade(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'upgrade_file' => ['required', 'file', 'mimes:zip', 'max:204800'],
        ]);

        $uploaded = $validated['upgrade_file'];

        $stagingDir = storage_path('app/system-upgrades');
        File::ensureDirectoryExists($stagingDir);

        $runId = now()->format('YmdHis').'-'.Str::lower(Str::random(8));
        $zipPath = $stagingDir.DIRECTORY_SEPARATOR.$runId.'.zip';
        $extractDir = $stagingDir.DIRECTORY_SEPARATOR.'extract-'.$runId;

        $uploaded->move($stagingDir, basename($zipPath));

        try {
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new RuntimeException('Unable to open the uploaded ZIP file.');
            }

            File::ensureDirectoryExists($extractDir);
            if (! $zip->extractTo($extractDir)) {
                $zip->close();
                throw new RuntimeException('Unable to extract the ZIP file.');
            }
            $zip->close();

            $sourceRoot = $this->detectSourceRoot($extractDir);
            $sync = $this->syncUpgradeFiles($sourceRoot);

            app(AuditLogger::class)->log(
                'developer.dashboard.system.upgrade',
                null,
                'System upgrade package applied',
                [
                    'run_id' => $runId,
                    'copied' => $sync['copied'],
                    'overwritten' => $sync['overwritten'],
                    'skipped' => $sync['skipped'],
                ]
            );

            $results = [[
                'label' => 'System Upgrade',
                'command' => 'upload:zip '.$uploaded->getClientOriginalName(),
                'exit_code' => 0,
                'output' => 'Upgrade package applied. Copied: '.$sync['copied'].', Overwritten: '.$sync['overwritten'].', Skipped: '.$sync['skipped'].'.',
                'started_at' => now()->toDateTimeString(),
                'ended_at' => now()->toDateTimeString(),
            ]];

            return redirect()
                ->route('developer.dashboard')
                ->with('status', 'System upgrade applied successfully.')
                ->with('developer_dashboard.results', $results);
        } catch (\Throwable $e) {
            return redirect()
                ->route('developer.dashboard')
                ->withErrors([
                    'upgrade_file' => 'Upgrade failed: '.$e->getMessage(),
                ]);
        } finally {
            if (File::exists($zipPath)) {
                File::delete($zipPath);
            }
            if (File::isDirectory($extractDir)) {
                File::deleteDirectory($extractDir);
            }
        }
    }

    /**
     * @return array{copied:int, overwritten:int, skipped:int}
     */
    private function syncUpgradeFiles(string $sourceRoot): array
    {
        $copied = 0;
        $overwritten = 0;
        $skipped = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceRoot, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $entry) {
            if (! $entry->isFile()) {
                continue;
            }

            $source = (string) $entry->getPathname();
            $relative = ltrim(substr($source, strlen($sourceRoot)), DIRECTORY_SEPARATOR);
            $relative = str_replace('\\', '/', $relative);

            if ($relative === '' || $this->isProtectedPath($relative)) {
                $skipped++;
                continue;
            }

            $target = base_path($relative);
            $targetDir = dirname($target);

            if (! is_dir($targetDir)) {
                File::ensureDirectoryExists($targetDir);
            }

            if (is_file($target)) {
                $overwritten++;
            }

            if (! @copy($source, $target)) {
                throw new RuntimeException('Unable to copy upgrade file: '.$relative);
            }

            $copied++;
        }

        return [
            'copied' => $copied,
            'overwritten' => $overwritten,
            'skipped' => $skipped,
        ];
    }

    private function detectSourceRoot(string $extractDir): string
    {
        $entries = array_values(array_filter(scandir($extractDir) ?: [], function ($item) {
            return $item !== '.' && $item !== '..';
        }));

        if (count($entries) === 1) {
            $single = $extractDir.DIRECTORY_SEPARATOR.$entries[0];
            if (is_dir($single)) {
                return $single;
            }
        }

        return $extractDir;
    }

    private function isProtectedPath(string $relativePath): bool
    {
        $path = ltrim(str_replace('\\', '/', $relativePath), '/');

        $exact = [
            '.env',
            'public/storage',
        ];

        foreach ($exact as $match) {
            if ($path === $match) {
                return true;
            }
        }

        $prefixes = [
            '.git/',
            'storage/',
            'bootstrap/cache/',
            'node_modules/',
        ];

        foreach ($prefixes as $prefix) {
            if (Str::startsWith($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{label:string,command:string,exit_code:int,output:string,started_at:string,ended_at:string}
     */
    private function executeCommand(string $command, string $label): array
    {
        $startedAt = now()->toDateTimeString();

        try {
            $exitCode = Artisan::call($command);
            $output = trim(Artisan::output());
        } catch (\Throwable $e) {
            $exitCode = 1;
            $output = 'Exception: '.$e->getMessage();
        }

        return [
            'label' => $label,
            'command' => $command,
            'exit_code' => $exitCode,
            'output' => $output !== '' ? $output : '(no output)',
            'started_at' => $startedAt,
            'ended_at' => now()->toDateTimeString(),
        ];
    }
}
