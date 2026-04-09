<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureProfileCompleted
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (
            $user &&
            $user->is_verified_by_admin != 2 &&
            !$request->routeIs('complete-profile.*') &&
            !$request->routeIs('logout') &&
            !$request->routeIs('login') &&
            !$request->routeIs('register') &&
            !$request->routeIs('auth.google') &&
            !$request->routeIs('log.viewer') &&
            !$request->routeIs('verification.notice') &&
            !$request->routeIs('verification.verify') &&
            !$request->routeIs('verification.send')
        ) {
            return redirect()->route('complete-profile.show');
        }

        return $next($request);
    }
}
