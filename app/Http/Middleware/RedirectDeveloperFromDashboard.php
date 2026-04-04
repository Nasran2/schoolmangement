<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RedirectDeveloperFromDashboard
{
    /**
     * Redirect Developer role away from the main admin dashboard.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if ($user && $user->hasRole('Developer')) {
            return new RedirectResponse(route('developer.dashboard'));
        }

        return $next($request);
    }
}
