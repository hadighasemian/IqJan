<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
*/

// Bale webhook routes
Route::post('/webhook/bale', [WebhookController::class, 'handleWebhook'])->name('webhook.bale');
Route::post('/webhook/bale/set', [WebhookController::class, 'setWebhook'])->name('webhook.bale.set');
Route::get('/webhook/bale/info', [WebhookController::class, 'getBotInfo'])->name('webhook.bale.info');

// Health check route
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'service' => 'IqJan Bot Service'
    ]);
})->name('health');
