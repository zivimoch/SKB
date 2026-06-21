<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaseWorkspaceController;
use App\Http\Controllers\DeveloperPortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('cases.index'));
Route::get('/developers', [DeveloperPortalController::class, 'index'])->name('developers.index');
Route::get('/developers/api', [DeveloperPortalController::class, 'apiReference'])->name('developers.api');
Route::get('/developers/openapi.yaml', [DeveloperPortalController::class, 'openApi'])->name('developers.openapi');
Route::get('/developers/docs/{path}', [DeveloperPortalController::class, 'document'])
    ->where('path', '.*\.md')
    ->name('developers.document');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/cases', [CaseWorkspaceController::class, 'index'])->name('cases.index');
    Route::get('/cases/{id}', [CaseWorkspaceController::class, 'show'])->name('cases.show');
    Route::get('/cases/{id}/data', [CaseWorkspaceController::class, 'data'])->name('cases.data');
    Route::post('/cases/{id}/agendas', [CaseWorkspaceController::class, 'storeAgenda'])->name('cases.agendas.store');
    Route::post('/cases/{id}/reports/{reportUuid}', [CaseWorkspaceController::class, 'updateReport'])->name('cases.reports.update');
});
