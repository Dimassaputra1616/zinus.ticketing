<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AssetSyncController;

Route::post('/asset-sync', [AssetSyncController::class, 'store'])
    ->middleware('asset.sync')
    ->name('api.asset-sync');
