<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class OnlyAdminUnlocked
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->session()->get('onlyadmin.unlocked') === true) {
            return $next($request);
        }

        return redirect()->route('onlyadmin.index');
    }
}
