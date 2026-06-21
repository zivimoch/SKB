<?php

use App\Http\Controllers\Api\V1\ConnectivityController;
use App\Http\Controllers\Api\V1\IntegrationCaseController;
use App\Http\Controllers\Api\V1\ServiceLogExportController;
use Illuminate\Support\Facades\Route;

Route::get('/v1/health', [ConnectivityController::class, 'health']);
Route::get('/v1/exports/service-logs', ServiceLogExportController::class)
    ->middleware(['spreadsheet.export', 'throttle:30,1']);
Route::prefix('v1')->middleware(['integration.signature', 'throttle:integration'])->group(function (): void {
    Route::get('/integrations/me', [ConnectivityController::class, 'me'])
        ->middleware('integration.scope:connection:test');
    Route::post('/integrations/echo', [ConnectivityController::class, 'echo'])
        ->middleware('integration.scope:connection:test');
    Route::post('/integrations/cases/{externalCaseId}/sync', [IntegrationCaseController::class, 'upsert'])
        ->middleware('integration.scope:cases:write');
    Route::put('/integrations/cases/{externalCaseId}', [IntegrationCaseController::class, 'upsert'])
        ->middleware('integration.scope:cases:write');
    Route::get('/integrations/cases/{externalCaseId}', [IntegrationCaseController::class, 'show'])
        ->middleware('integration.scope:cases:read');
});
