<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AiService;
use App\Models\AiModel;
use App\Models\AiApiKey;

class AiServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create OpenRouter service
        $openRouterService = AiService::updateOrCreate(
            ['name' => 'openrouter'],
            [
            'name' => 'openrouter',
            'display_name' => 'OpenRouter',
            'api_url' => 'https://openrouter.ai/api',
            'default_model' => 'openai/gpt-oss-20b:free',
            'config' => [
                'app_name' => 'IqJanBot',
                'app_url' => 'https://iq-jan.salam-raya.ir',
                'temperature' => 0.7,
                'timeout' => 30,
            ],
            'is_active' => true,
            'is_available' => true,
            'priority' => 10,
            ]
        );

        // Create OpenRouter models
        $openRouterModels = [
            [
                'name' => 'deepseek/deepseek-chat-v3.1:free',
                'display_name' => 'DeepSeek Chat v3.1',
                'provider' => 'deepseek',
                'pricing_type' => 'free',
                'max_tokens' => 32768,
                'capabilities' => ['text', 'chat', 'code'],
                'is_default' => false,
                'priority' => 10,
            ],
            [
                'name' => 'tngtech/deepseek-r1t2-chimera:free',
                'display_name' => 'DeepSeek R1 Chimera',
                'provider' => 'deepseek',
                'pricing_type' => 'free',
                'max_tokens' => 32768,
                'capabilities' => ['text', 'chat', 'reasoning'],
                'is_default' => false,
                'priority' => 9,
            ],
            [
                'name' => 'z-ai/glm-4.5-air:free',
                'display_name' => 'GLM-4.5 Air',
                'provider' => 'z-ai',
                'pricing_type' => 'free',
                'max_tokens' => 128000,
                'capabilities' => ['text', 'chat', 'multimodal'],
                'is_default' => false,
                'priority' => 8,
            ],
            [
                'name' => 'qwen/qwen3-coder:free',
                'display_name' => 'Qwen3 Coder',
                'provider' => 'qwen',
                'pricing_type' => 'free',
                'max_tokens' => 32768,
                'capabilities' => ['text', 'chat', 'code', 'programming'],
                'is_default' => false,
                'priority' => 7,
            ],
            [
                'name' => 'google/gemini-2.0-flash-exp:free',
                'display_name' => 'Gemini 2.0 Flash Experimental',
                'provider' => 'google',
                'pricing_type' => 'free',
                'max_tokens' => 1000000,
                'capabilities' => ['text', 'chat', 'multimodal', 'reasoning'],
                'is_default' => false,
                'priority' => 6,
            ],
            [
                'name' => 'openai/gpt-oss-20b:free',
                'display_name' => 'GPT-OSS 20B',
                'provider' => 'openai',
                'pricing_type' => 'free',
                'max_tokens' => 8192,
                'capabilities' => ['text', 'chat'],
                'is_default' => true,
                'priority' => 5,
            ],
            [
                'name' => 'google/gemma-3-27b-it:free',
                'display_name' => 'Gemma 3 27B Instruction Tuned',
                'provider' => 'google',
                'pricing_type' => 'free',
                'max_tokens' => 8192,
                'capabilities' => ['text', 'chat', 'instruction'],
                'is_default' => false,
                'priority' => 4,
            ],
        ];

        foreach ($openRouterModels as $modelData) {
            AiModel::updateOrCreate(
                [
                    'ai_service_id' => $openRouterService->id,
                    'name' => $modelData['name']
                ],
                array_merge($modelData, [
                    'ai_service_id' => $openRouterService->id,
                    'is_active' => true,
                ])
            );
        }

        // Create OpenRouter API keys
        $openRouterApiKeys = [
            [
                'name' => 'OpenRouter Key 1',
                'api_key' => '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',
                'max_usage_per_day' => null, // No daily limit
                'priority' => 10,
            ]
        ];

        foreach ($openRouterApiKeys as $keyData) {
            AiApiKey::updateOrCreate(
                [
                    'ai_service_id' => $openRouterService->id,
                    'name' => $keyData['name']
                ],
                array_merge($keyData, [
                    'ai_service_id' => $openRouterService->id,
                    'is_active' => true,
                    'is_available' => true,
                    'usage_count' => 0,
                    'current_daily_usage' => 0,
                ])
            );
        }

        // Create other AI services (OpenAI, Gemini, etc.) for future use
        $otherServices = [
            [
                'name' => 'openai',
                'display_name' => 'OpenAI',
                'api_url' => 'https://api.openai.com/v1',
                'default_model' => 'gpt-3.5-turbo',
                'config' => [
                    'temperature' => 0.7,
                    'timeout' => 30,
                ],
                'is_active' => true,
                'is_available' => false, // Not configured yet
                'priority' => 5,
            ],
            [
                'name' => 'gemini',
                'display_name' => 'Google Gemini',
                'api_url' => 'https://generativelanguage.googleapis.com',
                'default_model' => 'gemini-pro',
                'config' => [
                    'temperature' => 0.7,
                    'timeout' => 30,
                ],
                'is_active' => true,
                'is_available' => false, // Not configured yet
                'priority' => 3,
            ],
            [
                'name' => 'mock',
                'display_name' => 'Mock AI Service',
                'api_url' => 'http://localhost',
                'default_model' => 'mock-model',
                'config' => [
                    'temperature' => 0.7,
                    'timeout' => 5,
                ],
                'is_active' => true,
                'is_available' => true,
                'priority' => 1,
            ],
        ];

        foreach ($otherServices as $serviceData) {
            AiService::updateOrCreate(
                ['name' => $serviceData['name']],
                $serviceData
            );
        }

        $this->command->info('AI services, models, and API keys seeded successfully!');
    }
}