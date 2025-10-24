<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DiagnoseOpenRouter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:diagnose-openrouter {api_key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose OpenRouter API key issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apiKey = $this->argument('api_key');
        
        $this->info('🔍 Diagnosing OpenRouter API Key Issues...');
        $this->newLine();

        try {
            // Test 1: Basic connectivity
            $this->info('Test 1: Basic connectivity to OpenRouter...');
            $response = Http::timeout(30)->get('https://openrouter.ai/api/v1/models');
            $this->info("Status: {$response->status()}");
            if ($response->successful()) {
                $this->info('✅ OpenRouter is accessible');
            } else {
                $this->error('❌ OpenRouter is not accessible');
                $this->error("Response: " . $response->body());
            }
            $this->newLine();

            // Test 2: API key authentication
            $this->info('Test 2: API key authentication...');
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->get('https://openrouter.ai/api/v1/auth/key');

            $this->info("Status: {$response->status()}");
            $this->info("Response: " . $response->body());

            if ($response->successful()) {
                $this->info('✅ API key is valid');
                $keyInfo = $response->json();
                if (isset($keyInfo['data'])) {
                    $data = $keyInfo['data'];
                    $this->info("   Name: " . ($data['name'] ?? 'Unknown'));
                    $this->info("   Credits: " . ($data['credits'] ?? 'Unknown'));
                    $this->info("   Usage: " . ($data['usage'] ?? 'Unknown'));
                    $this->info("   Limit: " . ($data['limit'] ?? 'Unknown'));
                }
            } else {
                $this->error('❌ API key authentication failed');
                $errorData = json_decode($response->body(), true);
                if (isset($errorData['error']['message'])) {
                    $this->error("   Error: " . $errorData['error']['message']);
                }
            }
            $this->newLine();

            // Test 3: Models endpoint
            $this->info('Test 3: Models endpoint access...');
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->get('https://openrouter.ai/api/v1/models');

            $this->info("Status: {$response->status()}");
            if ($response->successful()) {
                $this->info('✅ Models endpoint accessible');
                $models = $response->json();
                if (isset($models['data'])) {
                    $this->info("   Available models: " . count($models['data']));
                }
            } else {
                $this->error('❌ Models endpoint failed');
                $this->error("Response: " . $response->body());
            }
            $this->newLine();

            // Test 4: Chat completion
            $this->info('Test 4: Chat completion test...');
            $payload = [
                'model' => 'openai/gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Hello, this is a test message.'
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 50
            ];

            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => 'https://iq-jan.salam-raya.ir',
                    'X-Title' => 'IqJanBot'
                ])
                ->post('https://openrouter.ai/api/v1/chat/completions', $payload);

            $this->info("Status: {$response->status()}");
            if ($response->successful()) {
                $this->info('✅ Chat completion works');
                $data = $response->json();
                if (isset($data['choices'][0]['message']['content'])) {
                    $this->info("   Response: " . $data['choices'][0]['message']['content']);
                }
            } else {
                $this->error('❌ Chat completion failed');
                $this->error("Response: " . $response->body());
            }

        } catch (\Exception $e) {
            $this->error('❌ Diagnosis failed: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info('🔍 Diagnosis completed.');

        return 0;
    }
}
