<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (strtolower($user->role) === 'admin') {
            return $next($request);
        }

        $allowed_roles_lower = array_map('strtolower', $roles);


        if (!in_array(strtolower($user->role), $allowed_roles_lower)) {
            Log::warning('RoleMiddleware: Forbidden access.', [
                'user_role' => $user->role,
                'allowed_roles' => $roles,
            ]);
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
