<?php

namespace App\Providers;

use App\Services\SettingsService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        $this->applyMailSettings();
        View::share('schoolName', app('settings')->get('school.name', config('app.name')));

        // Ensure permissions for new modules exist
        if (Schema::hasTable('permissions')) {
            try {
                \Spatie\Permission\Models\Permission::findOrCreate('audit_logs.view');
                \Spatie\Permission\Models\Permission::findOrCreate('dashboard.widget.recent_activity.view');
            } catch (\Throwable $e) {
                // ignore creation failures
            }
        }

        $defaultYear = app('settings')->get('school.academic_year', date('Y').'-'.(date('Y') + 1));
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

    private function applyMailSettings(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

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
}
