<?php

namespace App\Providers;

use App\Services\SettingsService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('settings', function () {
            return new SettingsService();
        });
        // Register console command for auto-promotion
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\AutoPromoteStudents::class,
                \App\Console\Commands\RunDailyBackup::class,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        $this->forceCanonicalRootUrl();
        $this->applyMailSettings();
        $this->attachRequestLogContext();

        Gate::before(function ($user) {
            if ($user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['Super Admin', 'Admin'])) {
                return true;
            }

            return null;
        });

        $settings = app('settings');

        View::share('schoolName', $settings->get('school.name', config('app.name')));

        // Ensure permissions for new modules exist
        if ($this->tableExists('permissions')) {
            try {
                Permission::findOrCreate('audit_logs.view');
                Permission::findOrCreate('dashboard.widget.recent_activity.view');
                Permission::findOrCreate('reports.daily_ledger.view');
                Permission::findOrCreate('users.manage');
            } catch (\Throwable $e) {
                // ignore creation failures
            }
        }

        $defaultYear = $settings->get('school.academic_year', date('Y').'-'.(date('Y') + 1));
        $selectedYear = $defaultYear;
        if (! app()->runningInConsole() && request()?->hasSession()) {
            $selectedYear = request()->session()->get('academic_year', $defaultYear);
        }

        $years = [];
        $start = (int) date('Y') - 2;
        for ($i = 0; $i < 7; $i++) {
            $from = $start + $i;
            $to = $from + 1;
            $years[] = $from.'-'.$to;
        }

        View::share('selectedAcademicYear', $selectedYear);
        View::share('availableAcademicYears', $years);
    }

    private function forceCanonicalRootUrl(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        $request = request();
        $appUrl = (string) config('app.url', '');
        $requestUri = (string) $request->server('REQUEST_URI', '');

        $needsCanonicalRoot = str_contains($appUrl, '/public')
            || $requestUri === '/public'
            || str_starts_with($requestUri, '/public/');

        if (! $needsCanonicalRoot) {
            return;
        }

        $root = rtrim($request->getSchemeAndHttpHost(), '/');

        if ($appUrl !== '' && preg_match('/^https?:\/\//i', $appUrl) === 1) {
            $root = rtrim(preg_replace('#/public/?$#', '', $appUrl) ?? $root, '/');
        }

        URL::forceRootUrl($root);
    }

    private function applyMailSettings(): void
    {
        $s = app('settings');

        $host = (string) $s->get('smtp.host', '');
        if ($host === '') {
            return;
        }

        $port = (int) ($s->get('smtp.port', '0') ?: 0);
        $username = (string) $s->get('smtp.username', '');
        $password = (string) $s->get('smtp.password', '');
        $encryption = (string) $s->get('smtp.encryption', '');

        $scheme = null;
        if ($encryption === 'ssl') {
            $scheme = 'smtps';
        }
        if ($encryption === 'tls') {
            $scheme = 'smtp';
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.url' => null,
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => $port > 0 ? $port : 587,
            'mail.mailers.smtp.username' => $username !== '' ? $username : null,
            'mail.mailers.smtp.password' => $password !== '' ? $password : null,
            'mail.mailers.smtp.scheme' => $scheme,
            // Avoid long hangs when host/port is unreachable.
            'mail.mailers.smtp.timeout' => 15,
        ]);

        $fromAddress = (string) $s->get('smtp.from.address', $s->get('school.email', ''));
        $fromName = (string) $s->get('smtp.from.name', $s->get('school.name', config('app.name')));

        if ($fromAddress !== '') {
            config([
                'mail.from.address' => $fromAddress,
                'mail.from.name' => $fromName,
            ]);
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            Log::warning('Unable to check whether a database table exists.', [
                'table' => $table,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function attachRequestLogContext(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        $request = request();

        Log::withContext([
            'user_id' => $request->user()?->id,
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'path' => '/'.ltrim($request->path(), '/'),
            'action' => optional($request->route())->getActionName(),
        ]);
    }
}
