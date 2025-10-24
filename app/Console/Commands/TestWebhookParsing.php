<?php

namespace App\Console\Commands;

use App\Adapters\Messenger\BaleAdapter;
use Illuminate\Console\Command;

class TestWebhookParsing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:test-parsing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test webhook payload parsing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing Webhook Payload Parsing...');
        $this->newLine();

        try {
            $baleAdapter = new BaleAdapter(config('services.bale.token'));

            // Test with the actual webhook payload from your logs
            $testWebhookPayload = [
                'update_id' => 170,
                'message' => [
                    'message_id' => 273,
                    'from' => [
                        'id' => 1038277246,
                        'is_bot' => false,
                        'first_name' => 'هادی قاسمیان',
                        'last_name' => null,
                        'username' => 'hadi_ghasemian'
                    ],
                    'date' => 1761314549,
                    'chat' => [
                        'id' => 1038277246,
                        'type' => 'private',
                        'username' => 'hadi_ghasemian',
                        'first_name' => 'هادی قاسمیان'
                    ],
                    'text' => 'o,fd?'
                ]
            ];

            $this->info('Testing with payload:');
            $this->info(json_encode($testWebhookPayload, JSON_PRETTY_PRINT));
            $this->newLine();

            $parsedData = $baleAdapter->parseWebhookPayload($testWebhookPayload);

            $this->info('Parsed data:');
            $this->info(json_encode($parsedData, JSON_PRETTY_PRINT));
            $this->newLine();

            // Check if all required keys exist
            $requiredKeys = ['message_id', 'chat_id', 'user_id', 'text', 'message_type'];
            foreach ($requiredKeys as $key) {
                if (isset($parsedData[$key])) {
                    $this->info("✅ {$key}: {$parsedData[$key]}");
                } else {
                    $this->error("❌ Missing key: {$key}");
                }
            }

        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
