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
        $allowedExternalEmails = config('company.external_email_allowlist', []);
        $currentEmail = strtolower((string) auth()->user()?->email);

        if (auth()->check() && (auth()->user()->isSuperAdmin() || in_array($currentEmail, $allowedExternalEmails, true))) {
            return $next($request);
        }

        abort(403, 'Akses ditolak — Halaman ini hanya untuk Super Admin.');
    }
}
