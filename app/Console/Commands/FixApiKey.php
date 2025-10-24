<?php

namespace App\Console\Commands;

use App\Models\AiApiKey;
use Illuminate\Console\Command;

class FixApiKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:fix-key {--key=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix the OpenRouter API key';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔑 Fixing OpenRouter API Key...');
        $this->newLine();

        try {
            // Get the API key from command line or use default
            $newApiKey = $this->option('key') ?: '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';

            // Find the OpenRouter API key
            $apiKey = AiApiKey::whereHas('aiService', function ($query) {
                $query->where('name', 'openrouter');
            })->first();

            if (!$apiKey) {
                $this->error('❌ No OpenRouter API key found in database');
                return 1;
            }

            $this->info("Current API key: " . substr($apiKey->api_key, 0, 20) . "...");
            $this->info("New API key: " . substr($newApiKey, 0, 20) . "...");

            // Update the API key
            $apiKey->api_key = $newApiKey;
            $apiKey->save();

            $this->info('✅ API key updated successfully!');
            $this->newLine();

            // Test the new API key
            $this->info('Testing the new API key...');
            
            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $newApiKey,
                    'Content-Type' => 'application/json'
                ])
                ->get('https://openrouter.ai/api/v1/auth/key');

            if ($response->successful()) {
                $this->info('✅ API key is valid!');
                $keyInfo = $response->json();
                if (isset($keyInfo['data'])) {
                    $this->info("   Credits: " . ($keyInfo['data']['credits'] ?? 'Unknown'));
                    $this->info("   Usage: " . ($keyInfo['data']['usage'] ?? 'Unknown'));
                }
            } else {
                $this->error('❌ API key is still invalid');
                $this->error("   Response: " . $response->body());
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Fix failed: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info('🎉 API key fix completed!');

        return 0;
    }
}
