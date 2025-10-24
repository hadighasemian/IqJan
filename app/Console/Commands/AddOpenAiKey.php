<?php

namespace App\Console\Commands;

use App\Models\AiService;
use App\Models\AiApiKey;
use Illuminate\Console\Command;

class AddOpenAiKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:add-openai-key {api_key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add OpenAI API key to the system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apiKey = $this->argument('api_key');
        
        $this->info('🔑 Adding OpenAI API Key...');
        $this->newLine();

        try {
            // Find OpenAI service
            $openaiService = AiService::where('name', 'openai_direct')->first();
            
            if (!$openaiService) {
                $this->error('❌ OpenAI service not found. Run: php artisan ai:setup-alternative');
                return 1;
            }

            // Test the API key
            $this->info('Testing OpenAI API key...');
            
            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->get('https://api.openai.com/v1/models');

            if (!$response->successful()) {
                $this->error('❌ OpenAI API key is invalid');
                $this->error("Response: " . $response->body());
                return 1;
            }

            $this->info('✅ OpenAI API key is valid');

            // Add the API key
            $aiApiKey = AiApiKey::updateOrCreate(
                [
                    'ai_service_id' => $openaiService->id,
                    'name' => 'OpenAI API Key'
                ],
                [
                    'ai_service_id' => $openaiService->id,
                    'name' => 'OpenAI API Key',
                    'api_key' => $apiKey,
                    'max_usage_per_day' => null,
                    'is_active' => true,
                    'is_available' => true,
                    'usage_count' => 0,
                    'current_daily_usage' => 0,
                    'priority' => 10,
                ]
            );

            // Update service availability
            $openaiService->update(['is_available' => true]);

            $this->info('✅ OpenAI API key added successfully!');
            $this->newLine();
            
            $this->info('You can now test with:');
            $this->info('php artisan ai:test --service=openai_direct');

        } catch (\Exception $e) {
            $this->error('❌ Failed to add OpenAI API key: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
