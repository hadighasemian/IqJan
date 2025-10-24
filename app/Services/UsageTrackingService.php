<?php

namespace App\Services;

use App\Models\AiApiKey;
use App\Models\AiService;
use Illuminate\Support\Facades\Log;

class UsageTrackingService
{
    public function trackUsage(AiApiKey $apiKey, array $usageData): void
    {
        try {
            // Update API key usage statistics
            $apiKey->incrementUsage($usageData);

            // Log usage for monitoring
            Log::info('AI API usage tracked', [
                'api_key_id' => $apiKey->id,
                'ai_service' => $apiKey->aiService->name,
                'usage_data' => $usageData,
                'total_usage' => $apiKey->usage_count,
                'daily_usage' => $apiKey->current_daily_usage
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to track AI usage', [
                'error' => $e->getMessage(),
                'api_key_id' => $apiKey->id,
                'usage_data' => $usageData
            ]);
        }
    }

    public function getUsageStats(string $aiServiceName = null): array
    {
        try {
            $query = AiApiKey::query();
            
            if ($aiServiceName) {
                $query->whereHas('aiService', function ($q) use ($aiServiceName) {
                    $q->where('name', $aiServiceName);
                });
            }

            $apiKeys = $query->with('aiService')->get();

            $stats = [];
            foreach ($apiKeys as $apiKey) {
                $stats[] = [
                    'service_name' => $apiKey->aiService->name,
                    'key_name' => $apiKey->name,
                    'total_usage' => $apiKey->usage_count,
                    'daily_usage' => $apiKey->current_daily_usage,
                    'max_daily_usage' => $apiKey->max_usage_per_day,
                    'last_used_at' => $apiKey->last_used_at,
                    'is_active' => $apiKey->is_active,
                    'is_available' => $apiKey->is_available
                ];
            }

            return $stats;

        } catch (\Exception $e) {
            Log::error('Failed to get usage stats', [
                'error' => $e->getMessage(),
                'ai_service' => $aiServiceName
            ]);
            
            return [];
        }
    }

    public function resetDailyUsage(): void
    {
        try {
            AiApiKey::where('last_usage_date', '!=', now()->toDateString())
                ->update(['current_daily_usage' => 0]);

            Log::info('Daily usage reset completed');
        } catch (\Exception $e) {
            Log::error('Failed to reset daily usage', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function checkApiKeyAvailability(string $aiServiceName): array
    {
        try {
            $aiService = AiService::where('name', $aiServiceName)->first();
            if (!$aiService) {
                return ['available' => false, 'reason' => 'Service not found'];
            }

            $availableKeys = $aiService->getAvailableApiKeys()->get();
            
            if ($availableKeys->isEmpty()) {
                return ['available' => false, 'reason' => 'No API keys available'];
            }

            $unlimitedKeys = $availableKeys->filter(function ($key) {
                return !$key->isUsageLimitReached();
            });

            if ($unlimitedKeys->isEmpty()) {
                return ['available' => false, 'reason' => 'All API keys reached usage limit'];
            }

            return [
                'available' => true,
                'available_keys' => $unlimitedKeys->count(),
                'total_keys' => $availableKeys->count()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to check API key availability', [
                'error' => $e->getMessage(),
                'ai_service' => $aiServiceName
            ]);
            
            return ['available' => false, 'reason' => 'Error checking availability'];
        }
    }
}
