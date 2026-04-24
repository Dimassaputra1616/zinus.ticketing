<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->email === 'dimassputra1616@gmail.com')) {
            return $next($request);
        }

        abort(403, 'Akses ditolak — Halaman ini hanya untuk Super Admin.');
    }
}
