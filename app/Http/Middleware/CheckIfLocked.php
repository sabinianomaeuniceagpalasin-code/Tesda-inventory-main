<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfLocked
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            auth()->check() &&
            session('system_locked') &&
            !$request->routeIs('lock.screen') &&
            !$request->routeIs('unlock') &&
            !$request->routeIs('logout')
        ) {
            return redirect()->route('lock.screen');
        }

        return $next($request);
    }
}