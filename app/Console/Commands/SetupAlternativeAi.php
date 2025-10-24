<?php

namespace App\Console\Commands;

use App\Models\AiService;
use App\Models\AiModel;
use App\Models\AiApiKey;
use Illuminate\Console\Command;

class SetupAlternativeAi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:setup-alternative';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup alternative AI service when OpenRouter is blocked';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🤖 Setting up Alternative AI Service...');
        $this->newLine();

        try {
            // Option 1: Use OpenAI directly
            $this->info('Option 1: Setting up OpenAI service...');
            
            $openaiService = AiService::updateOrCreate(
                ['name' => 'openai_direct'],
                [
                    'name' => 'openai_direct',
                    'display_name' => 'OpenAI Direct',
                    'api_url' => 'https://api.openai.com/v1',
                    'default_model' => 'gpt-3.5-turbo',
                    'config' => [
                        'app_name' => 'IqJanBot',
                        'app_url' => 'https://iq-jan.salam-raya.ir',
                        'temperature' => 0.7,
                        'timeout' => 30,
                    ],
                    'is_active' => true,
                    'is_available' => false, // Will be set to true when API key is added
                    'priority' => 5,
                ]
            );

            $this->info('✅ OpenAI service created');

            // Add OpenAI models
            $openaiModels = [
                [
                    'name' => 'gpt-3.5-turbo',
                    'display_name' => 'GPT-3.5 Turbo',
                    'provider' => 'openai',
                    'pricing_type' => 'paid',
                    'max_tokens' => 4096,
                    'capabilities' => ['text', 'chat'],
                    'is_default' => true,
                    'priority' => 10,
                ],
                [
                    'name' => 'gpt-4',
                    'display_name' => 'GPT-4',
                    'provider' => 'openai',
                    'pricing_type' => 'paid',
                    'max_tokens' => 8192,
                    'capabilities' => ['text', 'chat'],
                    'is_default' => false,
                    'priority' => 9,
                ]
            ];

            foreach ($openaiModels as $modelData) {
                AiModel::updateOrCreate(
                    [
                        'ai_service_id' => $openaiService->id,
                        'name' => $modelData['name']
                    ],
                    array_merge($modelData, [
                        'ai_service_id' => $openaiService->id,
                        'is_active' => true,
                    ])
                );
            }

            $this->info('✅ OpenAI models added');

            // Option 2: Use a mock service for testing
            $this->info('Option 2: Setting up mock service for testing...');
            
            $mockService = AiService::updateOrCreate(
                ['name' => 'mock'],
                [
                    'name' => 'mock',
                    'display_name' => 'Mock AI Service',
                    'api_url' => 'http://localhost',
                    'default_model' => 'mock-model',
                    'config' => [
                        'app_name' => 'IqJanBot',
                        'app_url' => 'https://iq-jan.salam-raya.ir',
                        'temperature' => 0.7,
                        'timeout' => 5,
                    ],
                    'is_active' => true,
                    'is_available' => true,
                    'priority' => 1,
                ]
            );

            $this->info('✅ Mock service created');

            // Add mock model
            AiModel::updateOrCreate(
                [
                    'ai_service_id' => $mockService->id,
                    'name' => 'mock-model'
                ],
                [
                    'ai_service_id' => $mockService->id,
                    'name' => 'mock-model',
                    'display_name' => 'Mock Model',
                    'provider' => 'mock',
                    'pricing_type' => 'free',
                    'max_tokens' => 1000,
                    'capabilities' => ['text', 'chat'],
                    'is_active' => true,
                    'is_default' => true,
                    'priority' => 10,
                ]
            );

            $this->info('✅ Mock model added');

            // Add mock API key
            AiApiKey::updateOrCreate(
                [
                    'ai_service_id' => $mockService->id,
                    'name' => 'Mock API Key'
                ],
                [
                    'ai_service_id' => $mockService->id,
                    'name' => 'Mock API Key',
                    'api_key' => 'mock-api-key-12345',
                    'max_usage_per_day' => null,
                    'is_active' => true,
                    'is_available' => true,
                    'usage_count' => 0,
                    'current_daily_usage' => 0,
                    'priority' => 10,
                ]
            );

            $this->info('✅ Mock API key added');

            $this->newLine();
            $this->info('🎉 Alternative AI services setup completed!');
            $this->newLine();
            
            $this->info('Next steps:');
            $this->info('1. Get an OpenAI API key from https://platform.openai.com/api-keys');
            $this->info('2. Run: php artisan ai:add-openai-key YOUR_OPENAI_KEY');
            $this->info('3. Or test with mock service: php artisan ai:test --service=mock');

        } catch (\Exception $e) {
            $this->error('❌ Setup failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
