<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SystemLockMiddleware
{
    /**
     * When enabled, blocks the app for everyone except the secret admin link.
     */
    public function handle(Request $request, Closure $next)
    {
        $settings = app('settings');

        $enabled = (string) $settings->get('system.lock.enabled', '0') === '1';
        if (! $enabled) {
            return $next($request);
        }

        $path = ltrim($request->path(), '/');

        // Always allow the secret admin entry and unlock actions.
        if ($path === 'onlyadmin' || Str::startsWith($path, 'onlyadmin/')) {
            return $next($request);
        }

        // Allow developer console to manage maintenance while lock is active.
        if ($path === 'developer' || Str::startsWith($path, 'developer/')) {
            return $next($request);
        }

        // Allow auth pages so developer can still sign in to disable lock mode.
        $authPaths = [
            'login',
            'logout',
            'forgot-password',
            'reset-password',
            'password',
        ];

        foreach ($authPaths as $authPath) {
            if ($path === $authPath || Str::startsWith($path, $authPath.'/')) {
                return $next($request);
            }
        }

        // Allow health check.
        if ($path === 'up') {
            return $next($request);
        }

        // Allow essential public assets.
        $allowedPrefixes = [
            'build/',
            'storage/',
        ];

        foreach ($allowedPrefixes as $prefix) {
            if (Str::startsWith($path.'/', $prefix)) {
                return $next($request);
            }
        }

        if (in_array($path, ['favicon.ico', 'robots.txt'], true)) {
            return $next($request);
        }

        return response()->view('system.locked-521', [
            'host' => $request->getHost(),
        ], 521);
    }
}
