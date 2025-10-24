<?php

namespace App\Console\Commands;

use App\Models\AiService;
use App\Models\AiApiKey;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DebugAiService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:debug';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug AI service connection and API issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Debugging AI Service...');
        $this->newLine();

        try {
            // Step 1: Check database configuration
            $this->info('Step 1: Checking database configuration...');
            $openRouterService = AiService::where('name', 'openrouter')->first();
            
            if (!$openRouterService) {
                $this->error('❌ OpenRouter service not found in database');
                return 1;
            }

            $this->info("✅ Found AI Service: {$openRouterService->display_name}");
            $this->info("   API URL: {$openRouterService->api_url}");
            $this->info("   Default Model: {$openRouterService->default_model}");
            $this->info("   Config: " . json_encode($openRouterService->config));
            $this->newLine();

            // Step 2: Check API key
            $this->info('Step 2: Checking API key...');
            $apiKey = $openRouterService->getAvailableApiKeys()->first();
            
            if (!$apiKey) {
                $this->error('❌ No available API key found');
                return 1;
            }

            $this->info("✅ Found API Key: {$apiKey->name}");
            $this->info("   Key starts with: " . substr($apiKey->api_key, 0, 20) . "...");
            $this->info("   Usage Count: {$apiKey->usage_count}");
            $this->info("   Daily Usage: {$apiKey->current_daily_usage}");
            $this->newLine();

            // Step 3: Test basic connectivity
            $this->info('Step 3: Testing basic connectivity...');
            
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey->api_key,
                    'Content-Type' => 'application/json'
                ])
                ->get('https://openrouter.ai/api/v1/models');

            $this->info("   Status Code: {$response->status()}");
            $this->info("   Response Headers: " . json_encode($response->headers()));
            
            if ($response->successful()) {
                $this->info('✅ Basic connectivity works');
                $models = $response->json();
                $this->info("   Available models: " . (isset($models['data']) ? count($models['data']) : 0));
            } else {
                $this->error('❌ Basic connectivity failed');
                $this->error("   Response: " . $response->body());
                $this->newLine();
                
                // Try without API key to see if it's an auth issue
                $this->info('Testing without API key...');
                $response2 = Http::timeout(30)->get('https://openrouter.ai/api/v1/models');
                $this->info("   Status without auth: {$response2->status()}");
                $this->error("   Response: " . $response2->body());
            }
            $this->newLine();

            // Step 4: Test chat completion endpoint
            $this->info('Step 4: Testing chat completion endpoint...');
            
            $payload = [
                'model' => $openRouterService->default_model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Hello, this is a test message.'
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 100
            ];

            $this->info("   Payload: " . json_encode($payload, JSON_PRETTY_PRINT));

            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey->api_key,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => $openRouterService->config['app_url'] ?? 'https://iq-jan.salam-raya.ir',
                    'X-Title' => $openRouterService->config['app_name'] ?? 'IqJanBot'
                ])
                ->post('https://openrouter.ai/api/v1/chat/completions', $payload);

            $this->info("   Status Code: {$response->status()}");
            $this->info("   Response Headers: " . json_encode($response->headers()));
            
            if ($response->successful()) {
                $this->info('✅ Chat completion works!');
                $data = $response->json();
                if (isset($data['choices'][0]['message']['content'])) {
                    $this->info("   Response: " . $data['choices'][0]['message']['content']);
                }
                if (isset($data['usage'])) {
                    $this->info("   Usage: " . json_encode($data['usage']));
                }
            } else {
                $this->error('❌ Chat completion failed');
                $this->error("   Response: " . $response->body());
                
                // Try with a different model
                $this->info('Trying with a different model...');
                $payload['model'] = 'openai/gpt-3.5-turbo';
                
                $response2 = Http::timeout(60)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey->api_key,
                        'Content-Type' => 'application/json',
                        'HTTP-Referer' => $openRouterService->config['app_url'] ?? 'https://iq-jan.salam-raya.ir',
                        'X-Title' => $openRouterService->config['app_name'] ?? 'IqJanBot'
                    ])
                    ->post('https://openrouter.ai/api/v1/chat/completions', $payload);

                $this->info("   Status with different model: {$response2->status()}");
                $this->error("   Response: " . $response2->body());
            }
            $this->newLine();

            // Step 5: Check API key status
            $this->info('Step 5: Checking API key status...');
            
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey->api_key,
                    'Content-Type' => 'application/json'
                ])
                ->get('https://openrouter.ai/api/v1/auth/key');

            if ($response->successful()) {
                $this->info('✅ API key status retrieved');
                $keyInfo = $response->json();
                $this->info("   Key Info: " . json_encode($keyInfo, JSON_PRETTY_PRINT));
            } else {
                $this->error('❌ Failed to get API key status');
                $this->error("   Response: " . $response->body());
            }

        } catch (\Exception $e) {
            $this->error('❌ Debug failed with exception:');
            $this->error($e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        $this->newLine();
        $this->info('🔍 Debug completed. Check the output above for issues.');

        return 0;
    }
}
