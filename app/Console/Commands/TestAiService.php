<?php

namespace App\Console\Commands;

use App\Adapters\AiService\OpenRouterAdapter;
use App\Models\AiService;
use App\Models\AiApiKey;
use Illuminate\Console\Command;

class TestAiService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:test {--message=Hello, how are you?} {--model=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test AI service with a sample message';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing AI Service...');
        $this->newLine();

        try {
            // Get OpenRouter service
            $openRouterService = AiService::where('name', 'openrouter')->first();
            
            if (!$openRouterService) {
                $this->error('OpenRouter service not found in database. Please run: php artisan db:seed');
                return 1;
            }

            $this->info("✅ Found AI Service: {$openRouterService->display_name}");
            $this->info("   API URL: {$openRouterService->api_url}");
            $this->info("   Default Model: {$openRouterService->default_model}");
            $this->info("   Active: " . ($openRouterService->is_active ? 'Yes' : 'No'));
            $this->info("   Available: " . ($openRouterService->is_available ? 'Yes' : 'No'));
            $this->newLine();

            // Get API key
            $apiKey = $openRouterService->getAvailableApiKeys()->first();
            
            if (!$apiKey) {
                $this->error('No available API key found. Please check your database seeding.');
                return 1;
            }

            $this->info("✅ Found API Key: {$apiKey->name}");
            $this->info("   Usage Count: {$apiKey->usage_count}");
            $this->info("   Daily Usage: {$apiKey->current_daily_usage}");
            if ($apiKey->max_usage_per_day) {
                $this->info("   Daily Limit: {$apiKey->max_usage_per_day}");
            }
            $this->newLine();

            // Check if usage limit is reached
            if ($apiKey->isUsageLimitReached()) {
                $this->error('API key usage limit reached!');
                return 1;
            }

            // Initialize OpenRouter adapter
            $openRouterAdapter = new OpenRouterAdapter(
                $apiKey->api_key,
                $openRouterService->default_model,
                $openRouterService->config ?? []
            );

            $this->info("✅ OpenRouter Adapter initialized");
            $this->newLine();

            // Test AI service availability
            $this->info('Testing AI service availability...');
            $isAvailable = $openRouterAdapter->isAvailable();
            
            if (!$isAvailable) {
                $this->error('AI service is not available!');
                return 1;
            }

            $this->info('✅ AI service is available');
            $this->newLine();

            // Get available models
            $this->info('Fetching available models...');
            $models = $openRouterAdapter->getAvailableModels();
            
            if (empty($models)) {
                $this->warn('No models available (this might be normal for some API keys)');
            } else {
                $this->info('✅ Available models: ' . count($models));
                foreach (array_slice($models, 0, 5) as $model) {
                    $this->info("   - {$model['name']} ({$model['id']})");
                }
                if (count($models) > 5) {
                    $this->info("   ... and " . (count($models) - 5) . " more");
                }
            }
            $this->newLine();

            // Test message
            $testMessage = $this->option('message');
            $model = $this->option('model') ?: $openRouterService->default_model;
            
            $this->info("Testing with message: '{$testMessage}'");
            $this->info("Using model: {$model}");
            $this->newLine();

            // Send test message
            $this->info('Sending message to AI service...');
            $startTime = microtime(true);
            
            $response = $openRouterAdapter->sendMessage($testMessage, $model);
            
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);

            if ($response['success']) {
                $this->info('✅ AI service responded successfully!');
                $this->info("   Response time: {$duration}ms");
                $this->info("   Model used: {$response['model']}");
                
                if (!empty($response['usage'])) {
                    $this->info("   Usage:");
                    foreach ($response['usage'] as $key => $value) {
                        $this->info("     {$key}: {$value}");
                    }
                }
                
                $this->newLine();
                $this->info('AI Response:');
                $this->line($response['response']);
                
            } else {
                $this->error('❌ AI service failed!');
                $this->error("   Error: {$response['error']}");
                $this->error("   Response time: {$duration}ms");
                return 1;
            }

            $this->newLine();
            $this->info('🎉 AI service test completed successfully!');

        } catch (\Exception $e) {
            $this->error('❌ Test failed with exception:');
            $this->error($e->getMessage());
            return 1;
        }

        return 0;
    }
}
