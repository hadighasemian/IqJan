<?php

namespace App\Adapters\AiService\Contracts;

interface AiServiceInterface
{
    /**
     * Send a message to AI service and get response
     */
    public function sendMessage(string $message, ?string $model = null, array $options = []): array;

    /**
     * Get available models
     */
    public function getAvailableModels(): array;

    /**
     * Get service name
     */
    public function getName(): string;

    /**
     * Check if service is available
     */
    public function isAvailable(): bool;

    /**
     * Get usage statistics
     */
    public function getUsageStats(): array;
}
