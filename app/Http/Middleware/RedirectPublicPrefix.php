<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectPublicPrefix
{
    /**
     * Redirect /public/* URLs to canonical root-based URLs.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $uri = (string) $request->server('REQUEST_URI', '');

        if ($uri === '/public' || str_starts_with($uri, '/public/')) {
            $target = substr($uri, strlen('/public'));
            if ($target === '') {
                $target = '/';
            }

            return redirect($target, 301);
        }

        return $next($request);
    }
}
