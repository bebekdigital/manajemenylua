<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            // Biarkan lewat jika sedang mengakses route ganti password, logout, atau request api/internal
            if (! $request->routeIs('password.force_change*') && ! $request->is('logout') && ! $request->is('api/*')) {
                return redirect()->route('password.force_change');
            }
        }

        return $next($request);
    }
}
