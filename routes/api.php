<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AssetSyncController;
use App\Http\Controllers\Api\ConversationApiController;
use App\Http\Controllers\Api\ReportController;

// Asset sync endpoint (existing)
Route::post('/asset-sync', [AssetSyncController::class, 'store'])
    ->middleware('asset.sync')
    ->name('api.asset-sync');

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

    Route::get('/tickets/sync', [ReportController::class, 'syncToN8n'])
        ->name('api.v1.tickets.sync');
});
