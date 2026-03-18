<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AssetSyncController;
use App\Http\Controllers\Api\ConversationApiController;
use App\Http\Controllers\Api\ReportController;

// Asset sync endpoint (existing)
Route::post('/asset-sync', [AssetSyncController::class, 'store'])
    ->middleware('asset.sync')
    ->name('api.asset-sync');

// Temporary route to fix PostgreSQL sequences on production
Route::get('/fix-db', function () {
    try {
        \Illuminate\Support\Facades\DB::statement("SELECT setval('assets_id_seq', coalesce(max(id), 1), max(id) IS NOT null) FROM assets");
        \Illuminate\Support\Facades\DB::statement("SELECT setval('asset_sync_logs_id_seq', coalesce(max(id), 1), max(id) IS NOT null) FROM asset_sync_logs");
        return response()->json(['success' => true, 'message' => 'Database sequences successfully reset. You can run the sync agent again.']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()]);
    }
});

// n8n Live Chat API (v1)
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Conversations
    Route::get('/conversations', [ConversationApiController::class, 'index'])
        ->name('api.v1.conversations.index');
    
    Route::get('/conversations/{id}', [ConversationApiController::class, 'show'])
        ->name('api.v1.conversations.show');
    
    Route::post('/conversations/{id}/messages', [ConversationApiController::class, 'sendMessage'])
        ->middleware('throttle:60,1')
        ->name('api.v1.conversations.send-message');

    Route::post('/conversations/{id}/handoff', [ConversationApiController::class, 'handoff'])
        ->name('api.v1.conversations.handoff');
        
    // Reports
    Route::get('/reports/daily', [ReportController::class, 'daily'])
        ->name('api.v1.reports.daily');
});
