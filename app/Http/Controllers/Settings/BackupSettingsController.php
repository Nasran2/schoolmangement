<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BackupSettingsController extends Controller
{
    public function index(): View
    {
        $disk = Storage::disk('local');
        if (! $disk->exists('backups')) {
            $disk->makeDirectory('backups');
        }

        $backups = collect($disk->files('backups'))
            ->filter(fn (string $p) => str_ends_with($p, '.zip'))
            ->map(function (string $path) use ($disk) {
                return [
                    'name' => basename($path),
                    'size' => $disk->size($path),
                    'last_modified' => $disk->lastModified($path),
                ];
            })
            ->sortByDesc('last_modified')
            ->values();

        return view('settings.backups', [
            'backups' => $backups,
            'enabled' => app('settings')->get('backups.enabled', '1') === '1',
            'retention_days' => (int) (app('settings')->get('backups.retention_days', '30') ?: 30),
            'last_run_at' => (string) app('settings')->get('backups.last_run_at', ''),
            'last_status' => (string) app('settings')->get('backups.last_status', ''),
            'last_file' => (string) app('settings')->get('backups.last_file', ''),
            'last_error' => (string) app('settings')->get('backups.last_error', ''),
        ]);
    }

    public function updateConfig(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'in:0,1'],
            'retention_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        app('settings')->setMany([
            'backups.enabled' => (string) ($validated['enabled'] ?? '0'),
            'backups.retention_days' => (string) $validated['retention_days'],
        ], 'backups');

        app(AuditLogger::class)->log(
            'system.backup.settings.update',
            null,
            'Backup settings updated',
            [
                'enabled' => (string) ($validated['enabled'] ?? '0'),
                'retention_days' => (int) $validated['retention_days'],
            ]
        );

        return back()->with('status', 'Backup settings updated.');
    }

    public function run(Request $request): RedirectResponse
    {
        $retentionDays = (int) (app('settings')->get('backups.retention_days', '30') ?: 30);

        try {
            Artisan::call('school:backup', [
                '--retentionDays' => $retentionDays,
            ]);

            app(AuditLogger::class)->log(
                'system.backup.run_manual',
                null,
                'Backup triggered manually',
                [
                    'retention_days' => $retentionDays,
                ]
            );

            $output = trim((string) Artisan::output());

            return back()->with('status', $output !== '' ? $output : 'Backup started.');
        } catch (\Throwable $e) {
            Log::error('Manual backup trigger failed.', [
                'retention_days' => $retentionDays,
                'user_id' => $request->user()?->id,
                'route' => $request->route()?->getName(),
                'action' => optional($request->route())->getActionName(),
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'backup_run' => 'Backup failed to start. Please check logs and try again.',
            ]);
        }
    }

    public function download(string $file)
    {
        $file = basename($file);
        $path = 'backups/'.$file;

        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        app(AuditLogger::class)->log(
            'system.backup.download',
            null,
            'Backup downloaded',
            [
                'file' => $file,
            ]
        );

        return response()->download($disk->path($path), $file);
    }

    public function destroy(Request $request, string $file): RedirectResponse
    {
        $file = basename($file);
        $path = 'backups/'.$file;

        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        $disk->delete($path);

        app(AuditLogger::class)->log(
            'system.backup.delete',
            null,
            'Backup deleted',
            [
                'file' => $file,
            ]
        );

        return back()->with('status', 'Backup deleted.');
    }
}
