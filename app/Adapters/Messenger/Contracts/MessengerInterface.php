<?php

namespace App\Adapters\Messenger\Contracts;

interface MessengerInterface
{
    /**
     * Send a text message
     */
    public function sendMessage(string $chatId, string $text, ?string $replyToMessageId = null): array;

    /**
     * Edit an existing message
     */
    public function editMessage(string $chatId, string $messageId, string $text): array;

    /**
     * Send a typing indicator
     */
    public function sendTyping(string $chatId): array;

    /**
     * Get bot information
     */
    public function getBotInfo(): array;

    /**
     * Verify webhook signature
     */
    public function verifyWebhook(string $payload, string $signature): bool;

    /**
     * Parse incoming webhook payload
     */
    public function parseWebhookPayload(array $payload): array;

    /**
     * Get messenger name
     */
    public function getName(): string;
}
