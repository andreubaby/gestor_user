<?php

use App\Http\Controllers\TimeguardController;
use Illuminate\Support\Facades\Route;

// ─── TimeGuard Pro API ───────────────────────────────────────────────────────

// Workers
Route::get('/timeguard/workers',          [TimeguardController::class, 'listWorkers']);
Route::post('/timeguard/workers',         [TimeguardController::class, 'storeWorker']);
Route::put('/timeguard/workers/{id}',     [TimeguardController::class, 'updateWorker']);
Route::delete('/timeguard/workers/{id}',  [TimeguardController::class, 'destroyWorker']);

// Time Entries
Route::get('/timeguard/entries',          [TimeguardController::class, 'listEntries']);
Route::post('/timeguard/entries',         [TimeguardController::class, 'storeEntry']);
Route::put('/timeguard/entries/{id}',     [TimeguardController::class, 'updateEntry']);
Route::delete('/timeguard/entries/{id}',  [TimeguardController::class, 'destroyEntry']);

// Compensations
Route::get('/timeguard/compensations',         [TimeguardController::class, 'listCompensations']);
Route::post('/timeguard/compensations',        [TimeguardController::class, 'storeCompensation']);
Route::delete('/timeguard/compensations/{id}', [TimeguardController::class, 'destroyCompensation']);

// Bulk import desde localStorage
Route::post('/timeguard/import', [TimeguardController::class, 'import']);

// ─── OpenWA WhatsApp Webhooks ────────────────────────────────────────────────
// Nota: Esta ruta NO requiere CSRF token ni autenticación
Route::post('/webhooks/openwa', [\App\Http\Controllers\OpenWAWebhookController::class, 'handle'])
    ->name('api.webhooks.openwa')
    ->withoutMiddleware(['csrf']); // OpenWA webhook POST sin sesión

// Alias legacy/singular para paneles que envían /api/webhook/openwa
Route::post('/webhook/openwa', [\App\Http\Controllers\OpenWAWebhookController::class, 'handle'])
    ->name('api.webhook.openwa')
    ->withoutMiddleware(['csrf']);

