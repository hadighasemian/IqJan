<?php

namespace App\Services;

use App\Adapters\Messenger\Contracts\MessengerInterface;
use App\Adapters\AiService\Contracts\AiServiceInterface;
use App\Models\Message;
use App\Models\AiService;
use App\Models\AiModel;
use App\Models\AiApiKey;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MessageProcessorService
{
    private MessengerInterface $messenger;
    private AiServiceInterface $aiService;
    private UserService $userService;
    private GroupService $groupService;
    private UsageTrackingService $usageTrackingService;

    public function __construct(
        MessengerInterface $messenger,
        AiServiceInterface $aiService,
        UserService $userService,
        GroupService $groupService,
        UsageTrackingService $usageTrackingService
    ) {
        $this->messenger = $messenger;
        $this->aiService = $aiService;
        $this->userService = $userService;
        $this->groupService = $groupService;
        $this->usageTrackingService = $usageTrackingService;
    }

    public function processMessage(array $webhookData): array
    {
        try {
            DB::beginTransaction();

            // Parse webhook data
            $parsedData = $this->messenger->parseWebhookPayload($webhookData);
            
            if ($parsedData['type'] !== 'message') {
                return ['success' => false, 'message' => 'Not a message type'];
            }

            // Save or update user and group
            $user = $this->userService->findOrCreateUser($parsedData);
            $group = null;
            
            if ($parsedData['chat_type'] !== 'private') {
                $group = $this->groupService->findOrCreateGroup($parsedData);
            }

            // Save the message
            $message = $this->saveMessage($parsedData, $user, $group);

            // Send waiting message
            $waitingMessage = $this->sendWaitingMessage($parsedData['chat_id']);

            // Process with AI
            $aiResponse = $this->processWithAi($message);

            // Update message with AI response
            $message->update([
                'ai_response' => $aiResponse['response'],
                'ai_usage' => $aiResponse['usage'],
                'ai_model' => $aiResponse['model'],
                'processed_at' => now(),
                'processing_error' => $aiResponse['success'] ? null : $aiResponse['error']
            ]);

            // Edit waiting message with AI response
            $this->editWaitingMessage($parsedData['chat_id'], $waitingMessage['message_id'], $aiResponse['response']);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Message processed successfully',
                'ai_response' => $aiResponse['response']
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Message processing failed', [
                'error' => $e->getMessage(),
                'webhook_data' => $webhookData
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function saveMessage(array $parsedData, $user, $group): Message
    {
        return Message::create([
            'user_id' => $user->id,
            'group_id' => $group?->id,
            'provider' => $this->messenger->getName(),
            'external_message_id' => $parsedData['message_id'],
            'message_type' => $parsedData['message_type'],
            'content' => $parsedData['text'],
            'ai_service' => $this->aiService->getName(),
            'raw_payload' => $parsedData['raw_payload']
        ]);
    }

    private function sendWaitingMessage(string $chatId): array
    {
        $waitingText = "الان جواب می دم";
        return $this->messenger->sendMessage($chatId, $waitingText);
    }

    private function editWaitingMessage(string $chatId, string $messageId, string $response): void
    {
        $this->messenger->editMessage($chatId, $messageId, $response);
    }

    private function processWithAi(Message $message): array
    {
        try {
            // Get AI service configuration
            $aiService = AiService::where('name', $this->aiService->getName())->first();
            if (!$aiService) {
                throw new \Exception('AI service not found');
            }

            // Get default model
            $defaultModel = $aiService->getDefaultModel();
            $modelName = $defaultModel ? $defaultModel->name : 'openai/gpt-oss-20b:free';

            // Get API key
            $apiKey = $aiService->getAvailableApiKeys()->first();
            if (!$apiKey) {
                throw new \Exception('No available API key found');
            }

            // Check usage limits
            if ($apiKey->isUsageLimitReached()) {
                throw new \Exception('API key usage limit reached');
            }

            // Send to AI service
            $response = $this->aiService->sendMessage($message->content, $modelName);

            // Track usage
            if ($response['success']) {
                $this->usageTrackingService->trackUsage($apiKey, $response['usage']);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('AI processing failed', [
                'error' => $e->getMessage(),
                'message_id' => $message->id
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => 'متأسفانه در حال حاضر نمی‌توانم پاسخ دهم. لطفاً بعداً دوباره تلاش کنید.',
                'usage' => [],
                'model' => 'unknown'
            ];
        }
    }
}
