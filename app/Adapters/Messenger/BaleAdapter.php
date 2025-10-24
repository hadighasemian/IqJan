<?php

namespace App\Adapters\Messenger;

use App\Adapters\Messenger\Contracts\MessengerInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BaleAdapter implements MessengerInterface
{
    private string $token;
    private string $baseUrl = 'https://tapi.bale.ai/bot';

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function sendMessage(string $chatId, string $text, ?string $replyToMessageId = null): array
    {
        try {
            $payload = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML'
            ];

            if ($replyToMessageId) {
                $payload['reply_to_message_id'] = $replyToMessageId;
            }

            $response = Http::timeout(30)
                ->post("{$this->baseUrl}{$this->token}/sendMessage", $payload);

            if (!$response->successful()) {
                Log::error('Bale sendMessage failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload' => $payload
                ]);
                throw new \Exception('Failed to send message to Bale');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Bale sendMessage error', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
                'text' => $text
            ]);
            throw $e;
        }
    }

    public function editMessage(string $chatId, string $messageId, string $text): array
    {
        try {
            $payload = [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text,
                'parse_mode' => 'HTML'
            ];

            $response = Http::timeout(30)
                ->post("{$this->baseUrl}{$this->token}/editMessageText", $payload);

            if (!$response->successful()) {
                Log::error('Bale editMessage failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload' => $payload
                ]);
                throw new \Exception('Failed to edit message in Bale');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Bale editMessage error', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text
            ]);
            throw $e;
        }
    }

    public function sendTyping(string $chatId): array
    {
        try {
            $payload = [
                'chat_id' => $chatId,
                'action' => 'typing'
            ];

            $response = Http::timeout(10)
                ->post("{$this->baseUrl}{$this->token}/sendChatAction", $payload);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Bale sendTyping error', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId
            ]);
            return [];
        }
    }

    public function getBotInfo(): array
    {
        try {
            $response = Http::timeout(30)
                ->get("{$this->baseUrl}{$this->token}/getMe");

            if (!$response->successful()) {
                throw new \Exception('Failed to get bot info from Bale');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Bale getBotInfo error', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function verifyWebhook(string $payload, string $signature): bool
    {
        // Bale webhook verification logic
        // For now, we'll assume webhooks are trusted
        // In production, implement proper signature verification
        return true;
    }

    public function parseWebhookPayload(array $payload): array
    {
        try {
            $update = $payload['update'] ?? $payload;
            
            if (!isset($update['message'])) {
                return [
                    'type' => 'unknown',
                    'data' => $update
                ];
            }

            $message = $update['message'];
            $chat = $message['chat'] ?? [];
            $from = $message['from'] ?? [];

            return [
                'type' => 'message',
                'message_id' => $message['message_id'] ?? null,
                'chat_id' => $chat['id'] ?? null,
                'chat_type' => $chat['type'] ?? 'private',
                'chat_title' => $chat['title'] ?? null,
                'user_id' => $from['id'] ?? null,
                'username' => $from['username'] ?? null,
                'first_name' => $from['first_name'] ?? null,
                'last_name' => $from['last_name'] ?? null,
                'text' => $message['text'] ?? null,
                'message_type' => $this->getMessageType($message),
                'date' => $message['date'] ?? null,
                'reply_to_message' => $message['reply_to_message'] ?? null,
                'raw_payload' => $payload
            ];
        } catch (\Exception $e) {
            Log::error('Bale parseWebhookPayload error', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
            throw $e;
        }
    }

    private function getMessageType(array $message): string
    {
        if (isset($message['text'])) return 'text';
        if (isset($message['photo'])) return 'photo';
        if (isset($message['video'])) return 'video';
        if (isset($message['document'])) return 'document';
        if (isset($message['voice'])) return 'voice';
        if (isset($message['audio'])) return 'audio';
        if (isset($message['sticker'])) return 'sticker';
        
        return 'unknown';
    }

    public function setWebhook(string $webhookUrl): array
    {
        try {
            $payload = [
                'url' => $webhookUrl
            ];

            $response = Http::timeout(30)
                ->post("{$this->baseUrl}{$this->token}/setWebhook", $payload);

            if (!$response->successful()) {
                Log::error('Bale setWebhook failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload' => $payload
                ]);
                throw new \Exception('Failed to set webhook URL');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Bale setWebhook error', [
                'error' => $e->getMessage(),
                'webhook_url' => $webhookUrl
            ]);
            throw $e;
        }
    }

    public function getName(): string
    {
        return 'bale';
    }
}
