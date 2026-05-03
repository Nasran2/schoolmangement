<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        // Run the automatic promotion check frequently; the command self-guards by date/year.
        $schedule->command('school:auto-promote')
            ->everyMinute()
            ->timezone(config('app.timezone', 'UTC'));
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append([
            \App\Http\Middleware\RedirectPublicPrefix::class,
            \App\Http\Middleware\SystemLockMiddleware::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'onlyadmin' => \App\Http\Middleware\OnlyAdminUnlocked::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            if ($e instanceof ValidationException) {
                // Keep Laravel's standard redirect-with-errors behavior for form validation.
                return null;
            }

            $status = match (true) {
                $e instanceof TokenMismatchException => 419,
                $e instanceof HttpExceptionInterface => $e->getStatusCode(),
                default => 500,
            };

            if ($status === 500 && config('app.debug')) {
                return null;
            }

            if (! in_array($status, [403, 404, 419, 422, 500], true)) {
                return null;
            }

            $view = 'errors.'.$status;
            if (! view()->exists($view)) {
                return null;
            }

            return response()->view($view, [
                'statusCode' => $status,
            ], $status);
        });
    })->create();
