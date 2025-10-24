<?php

namespace App\Http\Controllers;

use App\Services\MessageProcessorService;
use App\Adapters\Messenger\BaleAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    private MessageProcessorService $messageProcessor;
    private BaleAdapter $baleAdapter;

    public function __construct(
        MessageProcessorService $messageProcessor,
        BaleAdapter $baleAdapter
    ) {
        $this->messageProcessor = $messageProcessor;
        $this->baleAdapter = $baleAdapter;
    }

    public function handleWebhook(Request $request): Response
    {
        try {
            // Log incoming webhook
            Log::info('Webhook received', [
                'headers' => $request->headers->all(),
                'payload' => $request->all()
            ]);

            // Verify webhook signature
            $payload = $request->getContent();
            $signature = $request->header('X-Bale-Signature', '');
            
            if (!$this->baleAdapter->verifyWebhook($payload, $signature)) {
                Log::warning('Invalid webhook signature', [
                    'signature' => $signature,
                    'payload_length' => strlen($payload)
                ]);
                
                return response('Unauthorized', 401);
            }

            // Process the webhook
            $webhookData = $request->all();
            Log::info('Processing webhook data', ['webhook_data' => $webhookData]);
            $result = $this->messageProcessor->processMessage($webhookData);
            Log::info('Webhook processing result', ['result' => $result]);

            if ($result['success']) {
                Log::info('Webhook processed successfully', [
                    'result' => $result
                ]);
                
                return response('OK', 200);
            } else {
                Log::error('Webhook processing failed', [
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                
                return response('Processing failed', 500);
            }

        } catch (\Exception $e) {
            Log::error('Webhook handling failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response('Internal server error', 500);
        }
    }

    public function setWebhook(Request $request)
    {
        try {
            $webhookUrl = $request->input('webhook_url');
            
            if (!$webhookUrl) {
                return response('Webhook URL is required', 400);
            }

            // Set webhook URL using Bale API
            $response = $this->baleAdapter->setWebhook($webhookUrl);

            Log::info('Webhook URL set', [
                'webhook_url' => $webhookUrl,
                'response' => $response
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook URL set successfully',
                'data' => $response
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to set webhook URL', [
                'error' => $e->getMessage(),
                'webhook_url' => $request->input('webhook_url')
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getBotInfo()
    {
        try {
            $botInfo = $this->baleAdapter->getBotInfo();

            return response()->json([
                'success' => true,
                'data' => $botInfo
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get bot info', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
