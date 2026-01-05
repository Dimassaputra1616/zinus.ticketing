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
        $expected = trim((string) config('services.asset_sync.token'));
        $scopedTokens = config('services.asset_sync.tokens');
        $authorized = false;

        if ($incoming !== '' && is_array($scopedTokens)) {
            if (array_key_exists($incoming, $scopedTokens)) {
                $authorized = true;
            } else {
                foreach ($scopedTokens as $entry) {
                    if (! is_array($entry)) {
                        continue;
                    }

                    $entryToken = trim((string) ($entry['token'] ?? ''));
                    if ($entryToken !== '' && hash_equals($entryToken, $incoming)) {
                        $authorized = true;
                        break;
                    }
                }
            }
        }

        if (! $authorized && $expected !== '' && $incoming !== '') {
            $authorized = hash_equals($expected, $incoming);
        }

        if (! $incoming || ! $authorized) {
            Log::warning('Asset sync unauthorized', [
                'incoming_length' => strlen($incoming),
                'expected_length' => strlen($expected),
                'incoming_preview' => $incoming ? substr($incoming, 0, 6) . '...' . substr($incoming, -6) : null,
                'expected_preview' => $expected ? substr($expected, 0, 6) . '...' . substr($expected, -6) : null,
                'scoped_tokens' => is_array($scopedTokens) ? count($scopedTokens) : 0,
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
