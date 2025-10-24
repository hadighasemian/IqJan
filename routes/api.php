<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
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

// Test webhook route
Route::post('/webhook/test', function () {
    return response()->json([
        'status' => 'webhook working',
        'timestamp' => now(),
        'message' => 'Webhook endpoint is accessible'
    ]);
});

// Health check route
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'service' => 'IqJan Bot Service'
    ]);
});

// Test AI service route
Route::post('/test/ai', function (Request $request) {
    try {
        $message = $request->input('message', 'Hello, how are you?');
        $model = $request->input('model');
        
        // Get OpenRouter service
        $openRouterService = \App\Models\AiService::where('name', 'openrouter')->first();
        
        if (!$openRouterService) {
            return response()->json([
                'success' => false,
                'error' => 'OpenRouter service not found'
            ], 500);
        }

        // Get API key
        $apiKey = $openRouterService->getAvailableApiKeys()->first();
        
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'No available API key found'
            ], 500);
        }

        // Initialize OpenRouter adapter
        $openRouterAdapter = new \App\Adapters\AiService\OpenRouterAdapter(
            $apiKey->api_key,
            $openRouterService->default_model,
            $openRouterService->config ?? []
        );

        // Send message
        $response = $openRouterAdapter->sendMessage($message, $model);

        return response()->json([
            'success' => $response['success'],
            'message' => $message,
            'model' => $response['model'],
            'response' => $response['response'],
            'usage' => $response['usage'],
            'error' => $response['error'] ?? null
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});
