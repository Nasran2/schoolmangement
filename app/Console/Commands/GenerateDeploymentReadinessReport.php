<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class GenerateDeploymentReadinessReport extends Command
{
    protected $signature = 'school:deployment-report
        {--path=reports/deployment_readiness.pdf : Output path relative to storage/app}
        {--public : Also write a copy to public/deployment_readiness.pdf}
        {--include-routes=1 : Include the full route list (1/0)}
        {--company= : Vendor / company name to show in the report}
        {--school= : School name to show in the report}
        {--highlights=* : Customer-facing highlights (repeatable option)}';

    protected $description = 'Generate a PDF deployment readiness report (includes features/routes list).';

    public function handle(): int
    {
        $now = now();

        $outputRelativePath = ltrim((string) $this->option('path'), '/');
        if ($outputRelativePath === '') {
            $outputRelativePath = 'reports/deployment_readiness.pdf';
        }

        $includeRoutes = (string) $this->option('include-routes');
        $includeRoutes = !in_array(strtolower($includeRoutes), ['0', 'false', 'no'], true);

        $company = trim((string) ($this->option('company') ?? ''));
        $school = trim((string) ($this->option('school') ?? ''));
        $highlights = (array) ($this->option('highlights') ?? []);
        $highlights = collect($highlights)
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->values()
            ->all();

        $storageAppPath = storage_path('app');
        $outputAbsolutePath = $storageAppPath . DIRECTORY_SEPARATOR . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $outputRelativePath);
        File::ensureDirectoryExists(dirname($outputAbsolutePath));

        $controllers = $this->discoverControllers();
        $services = $this->discoverServices();
        $commands = $this->discoverCommands();

        $routes = [];
        if ($includeRoutes) {
            $routes = collect(Route::getRoutes())
                ->map(function ($route) {
                    $methods = array_values(array_diff($route->methods(), ['HEAD']));

                    return [
                        'methods' => implode('|', $methods),
                        'uri' => '/' . ltrim($route->uri(), '/'),
                        'name' => $route->getName(),
                        'action' => ltrim($route->getActionName() ?? '', '\\'),
                        'middleware' => $route->gatherMiddleware(),
                    ];
                })
                ->sortBy('uri')
                ->values()
                ->all();
        }

        $data = [
            'generated_at' => $now->toDateTimeString(),
            'customer' => [
                'company' => $company,
                'school' => $school,
                'highlights' => $highlights,
            ],
            'app' => [
                'name' => (string) config('app.name'),
                'env' => (string) config('app.env'),
                'debug' => (bool) config('app.debug'),
                'url' => (string) config('app.url'),
                'timezone' => (string) config('app.timezone'),
                'locale' => (string) config('app.locale'),
                'laravel_version' => App::version(),
                'php_version' => PHP_VERSION,
            ],
            'cache' => [
                'config_cached' => app()->configurationIsCached(),
                'routes_cached' => app()->routesAreCached(),
                'events_cached' => app()->eventsAreCached(),
                'views_cached' => File::exists(storage_path('framework/views')),
            ],
            'drivers' => [
                'cache' => (string) config('cache.default'),
                'db' => (string) config('database.default'),
                'queue' => (string) config('queue.default'),
                'session' => (string) config('session.driver'),
                'mail' => (string) config('mail.default'),
                'log' => (string) config('logging.default'),
            ],
            'storage' => [
                'public_storage_exists' => File::exists(public_path('storage')),
                'public_storage_is_link' => is_link(public_path('storage')),
                'storage_writable' => is_writable(storage_path()),
                'bootstrap_cache_writable' => is_writable(base_path('bootstrap/cache')),
            ],
            'audit_logs' => [
                'retention_days' => (int) config('audit_logs.retention_days', 10),
            ],
            'features' => $this->groupFeaturesFromControllers($controllers),
            'controllers' => $controllers,
            'services' => $services,
            'commands' => $commands,
            'routes' => $routes,
            'go_live_checklist' => $this->goLiveChecklist(),
        ];

        $pdf = Pdf::loadView('reports.deployment_readiness', $data)
            ->setPaper('a4', 'portrait');

        $pdf->save($outputAbsolutePath);

        $this->info('PDF generated: ' . $outputAbsolutePath);

        if ((bool) $this->option('public')) {
            $publicPath = public_path('deployment_readiness.pdf');
            File::copy($outputAbsolutePath, $publicPath);
            $this->info('Public copy written: ' . $publicPath);
        }

        return self::SUCCESS;
    }

    /** @return array<int, array{file:string,class:string,area:string}> */
    private function discoverControllers(): array
    {
        $base = app_path('Http/Controllers');

        return collect(File::allFiles($base))
            ->filter(fn ($f) => Str::endsWith($f->getFilename(), 'Controller.php'))
            ->map(function ($f) use ($base) {
                $relative = str_replace($base . DIRECTORY_SEPARATOR, '', $f->getPathname());
                $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);

                $class = 'App\\Http\\Controllers\\' . str_replace('/', '\\', Str::replaceLast('.php', '', $relative));

                return [
                    'file' => $relative,
                    'class' => $class,
                    'area' => $this->controllerAreaFromPath($relative),
                ];
            })
            ->sortBy('class')
            ->values()
            ->all();
    }

    /** @return array<int, array{file:string,class:string}> */
    private function discoverServices(): array
    {
        $base = app_path('Services');
        if (!File::isDirectory($base)) {
            return [];
        }

        return collect(File::allFiles($base))
            ->filter(fn ($f) => Str::endsWith($f->getFilename(), '.php'))
            ->map(function ($f) use ($base) {
                $relative = str_replace($base . DIRECTORY_SEPARATOR, '', $f->getPathname());
                $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);

                $class = 'App\\Services\\' . str_replace('/', '\\', Str::replaceLast('.php', '', $relative));

                return [
                    'file' => $relative,
                    'class' => $class,
                ];
            })
            ->sortBy('class')
            ->values()
            ->all();
    }

    /** @return array<int, array{file:string,class:string,signature:?string}> */
    private function discoverCommands(): array
    {
        $base = app_path('Console/Commands');
        if (!File::isDirectory($base)) {
            return [];
        }

        return collect(File::allFiles($base))
            ->filter(fn ($f) => Str::endsWith($f->getFilename(), '.php'))
            ->map(function ($f) use ($base) {
                $relative = str_replace($base . DIRECTORY_SEPARATOR, '', $f->getPathname());
                $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);

                $class = 'App\\Console\\Commands\\' . str_replace('/', '\\', Str::replaceLast('.php', '', $relative));

                $signature = null;
                try {
                    if (class_exists($class)) {
                        $instance = app($class);
                        $signature = property_exists($instance, 'signature') ? (string) $instance->signature : null;
                    }
                } catch (\Throwable) {
                    $signature = null;
                }

                return [
                    'file' => $relative,
                    'class' => $class,
                    'signature' => $signature,
                ];
            })
            ->sortBy('class')
            ->values()
            ->all();
    }

    private function controllerAreaFromPath(string $relativePath): string
    {
        if (Str::contains($relativePath, '/Settings/')) {
            return 'Settings';
        }
        if (Str::contains($relativePath, '/Rbac/')) {
            return 'RBAC';
        }
        if (Str::startsWith($relativePath, 'Auth/')) {
            return 'Auth';
        }

        return 'Core';
    }

    /** @param array<int, array{file:string,class:string,area:string}> $controllers */
    private function groupFeaturesFromControllers(array $controllers): array
    {
        $features = collect($controllers)
            ->map(function ($c) {
                $short = class_basename($c['class']);

                $module = match (true) {
                    Str::contains($c['file'], '/Settings/') => 'Settings',
                    Str::contains($c['file'], '/Rbac/') => 'Users & Roles (RBAC)',
                    Str::startsWith($c['file'], 'Auth/') => 'Authentication',
                    Str::startsWith($short, 'Student') => 'Students',
                    Str::startsWith($short, 'TeacherSalary') => 'Teacher Salaries',
                    Str::startsWith($short, 'Teacher') => 'Teachers',
                    Str::startsWith($short, 'Revenue') => 'Revenue / Fees',
                    Str::startsWith($short, 'Expense') => 'Expenses',
                    Str::startsWith($short, 'Report') => 'Reports',
                    Str::startsWith($short, 'AuditLog') => 'Audit Logs',
                    Str::startsWith($short, 'Seminar') => 'Seminars',
                    Str::startsWith($short, 'ExtraClass') => 'Extra Classes',
                    Str::startsWith($short, 'Sms') => 'SMS',
                    Str::startsWith($short, 'Promotion') => 'Promotions',
                    Str::startsWith($short, 'Printer') => 'Printing / Receipts',
                    Str::startsWith($short, 'Dashboard') => 'Dashboard',
                    Str::startsWith($short, 'ClassRoom') => 'Classrooms',
                    Str::startsWith($short, 'OnlyAdmin') => 'System Admin Lock',
                    default => 'Other',
                };

                return [
                    'module' => $module,
                    'controller' => $short,
                ];
            })
            ->groupBy('module')
            ->map(fn ($items) => $items->pluck('controller')->unique()->sort()->values()->all())
            ->sortKeys();

        return $features->all();
    }

    private function goLiveChecklist(): array
    {
        $checklist = [];

        $checklist[] = [
            'title' => 'Set production env variables',
            'detail' => 'APP_ENV=production, APP_DEBUG=false, APP_URL=https://your-domain',
            'status' => (config('app.env') === 'production' && !config('app.debug')) ? 'OK' : 'ACTION',
        ];

        $checklist[] = [
            'title' => 'Cache config/routes/views',
            'detail' => 'php artisan optimize (or config:cache, route:cache, view:cache)',
            'status' => (app()->configurationIsCached() && app()->routesAreCached()) ? 'OK' : 'RECOMMENDED',
        ];

        $checklist[] = [
            'title' => 'Storage + cache write permissions',
            'detail' => 'storage/ and bootstrap/cache must be writable by web server user',
            'status' => (is_writable(storage_path()) && is_writable(base_path('bootstrap/cache'))) ? 'OK' : 'ACTION',
        ];

        $checklist[] = [
            'title' => 'Database & migrations',
            'detail' => 'Confirm DB credentials, run php artisan migrate --force',
            'status' => 'MANUAL',
        ];

        $checklist[] = [
            'title' => 'Queue worker running',
            'detail' => 'If using database queue, run a worker via Supervisor/systemd',
            'status' => (config('queue.default') === 'sync') ? 'OK' : 'MANUAL',
        ];

        $checklist[] = [
            'title' => 'Scheduler running',
            'detail' => 'Set cron to run php artisan schedule:run every minute',
            'status' => 'MANUAL',
        ];

        $checklist[] = [
            'title' => 'Mail/SMS real providers configured',
            'detail' => 'mail.default and SMS settings must use real credentials in production',
            'status' => (config('mail.default') !== 'log') ? 'MANUAL' : 'ACTION',
        ];

        $checklist[] = [
            'title' => 'HTTPS + secure cookies',
            'detail' => 'Force HTTPS at the edge; set SESSION_SECURE_COOKIE=true',
            'status' => 'MANUAL',
        ];

        return $checklist;
    }
}
