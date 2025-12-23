<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;
use App\Http\Middleware\AdminMiddleware; // ✅ penting!
use App\Http\Middleware\VerifyAssetSyncToken;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware) {
        // ✅ daftar alias middleware admin di sini
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'asset.sync' => VerifyAssetSyncToken::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
