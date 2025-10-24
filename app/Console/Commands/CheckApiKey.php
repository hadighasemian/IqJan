<?php

namespace App\Console\Commands;

use App\Models\AiService;
use App\Models\AiApiKey;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckApiKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:check-key';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if the OpenRouter API key is valid';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔑 Checking OpenRouter API Key...');
        $this->newLine();

        try {
            // Get API key from database
            $openRouterService = AiService::where('name', 'openrouter')->first();
            
            if (!$openRouterService) {
                $this->error('❌ OpenRouter service not found');
                return 1;
            }

            $apiKey = $openRouterService->getAvailableApiKeys()->first();
            
            if (!$apiKey) {
                $this->error('❌ No API key found');
                return 1;
            }

            $this->info("API Key: " . substr($apiKey->api_key, 0, 20) . "...");
            $this->newLine();

            // Test the API key
            $this->info('Testing API key validity...');
            
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey->api_key,
                    'Content-Type' => 'application/json'
                ])
                ->get('https://openrouter.ai/api/v1/auth/key');

            $this->info("Status Code: {$response->status()}");
            
            if ($response->successful()) {
                $this->info('✅ API key is valid!');
                $keyInfo = $response->json();
                
                $this->info('Key Information:');
                if (isset($keyInfo['data'])) {
                    $data = $keyInfo['data'];
                    $this->info("  Name: " . ($data['name'] ?? 'Unknown'));
                    $this->info("  Credits: " . ($data['credits'] ?? 'Unknown'));
                    $this->info("  Usage: " . ($data['usage'] ?? 'Unknown'));
                    $this->info("  Limit: " . ($data['limit'] ?? 'Unknown'));
                }
                
                $this->newLine();
                
                // Test models endpoint
                $this->info('Testing models endpoint...');
                $modelsResponse = Http::timeout(30)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey->api_key,
                        'Content-Type' => 'application/json'
                    ])
                    ->get('https://openrouter.ai/api/v1/models');

                if ($modelsResponse->successful()) {
                    $this->info('✅ Models endpoint accessible');
                    $models = $modelsResponse->json();
                    if (isset($models['data'])) {
                        $this->info("  Available models: " . count($models['data']));
                        
                        // Show some free models
                        $freeModels = array_filter($models['data'], function($model) {
                            return isset($model['pricing']['prompt']) && $model['pricing']['prompt'] === '0';
                        });
                        
                        if (!empty($freeModels)) {
                            $this->info('  Free models available:');
                            foreach (array_slice($freeModels, 0, 5) as $model) {
                                $this->info("    - {$model['id']}");
                            }
                        }
                    }
                } else {
                    $this->error('❌ Models endpoint failed');
                    $this->error("  Response: " . $modelsResponse->body());
                }
                
            } else {
                $this->error('❌ API key is invalid or expired');
                $this->error("Response: " . $response->body());
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Check failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
