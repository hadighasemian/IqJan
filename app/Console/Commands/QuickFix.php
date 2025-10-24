<?php

namespace App\Console\Commands;

use App\Models\AiApiKey;
use Illuminate\Console\Command;

class QuickFix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:quick-fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quick fix for bot issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Quick Fix for Bot Issues...');
        $this->newLine();

        try {
            // Fix 1: Update API key
            $this->info('Fix 1: Updating API key...');
            
            $apiKey = AiApiKey::whereHas('aiService', function ($query) {
                $query->where('name', 'openrouter');
            })->first();

            if ($apiKey) {
                $newApiKey = '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';
                $apiKey->api_key = $newApiKey;
                $apiKey->save();
                $this->info('✅ API key updated');
            } else {
                $this->error('❌ API key not found');
            }

            // Fix 2: Test API key
            $this->info('Fix 2: Testing API key...');
            
            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $newApiKey,
                    'Content-Type' => 'application/json'
                ])
                ->get('https://openrouter.ai/api/v1/auth/key');

            if ($response->successful()) {
                $this->info('✅ API key is valid');
            } else {
                $this->error('❌ API key is invalid: ' . $response->body());
            }

            // Fix 3: Clear caches
            $this->info('Fix 3: Clearing caches...');
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            \Illuminate\Support\Facades\Artisan::call('route:clear');
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            $this->info('✅ Caches cleared');

            $this->newLine();
            $this->info('🎉 Quick fix completed!');
            $this->info('Now test your bot by sending a message.');

        } catch (\Exception $e) {
            $this->error('❌ Quick fix failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
