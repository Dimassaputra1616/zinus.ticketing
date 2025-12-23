<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyAssetSyncToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $incoming = trim((string) $request->bearerToken());
        $expected = trim((string) (config('services.asset_sync.token') ?? env('ASSET_SYNC_TOKEN')));

        if (! $incoming || ! $expected || ! hash_equals($expected, $incoming)) {
            Log::warning('Asset sync unauthorized', [
                'incoming_length' => strlen($incoming),
                'expected_length' => strlen($expected),
                'incoming_preview' => $incoming ? substr($incoming, 0, 6) . '...' . substr($incoming, -6) : null,
                'expected_preview' => $expected ? substr($expected, 0, 6) . '...' . substr($expected, -6) : null,
                'ip' => $request->ip(),
                'path' => $request->path(),
                'headers' => [
                    'authorization' => $request->header('Authorization'),
                ],
            ]);

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
