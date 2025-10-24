<?php

namespace App\Console\Commands;

use App\Services\MessageProcessorService;
use App\Adapters\Messenger\BaleAdapter;
use App\Adapters\AiService\OpenRouterAdapter;
use App\Services\UserService;
use App\Services\GroupService;
use App\Services\UsageTrackingService;
use App\Models\AiService;
use App\Models\AiApiKey;
use Illuminate\Console\Command;

class TestRealWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:test-real-webhook {--message=سلام}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test webhook with the actual payload structure from Bale';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🤖 Testing Real Webhook Structure...');
        $this->newLine();

        $testMessage = $this->option('message');

        try {
            // Initialize services
            $baleAdapter = new BaleAdapter(config('services.bale.token'));
            
            $openRouterService = AiService::where('name', 'openrouter')->first();
            $apiKey = $openRouterService?->getAvailableApiKeys()->first();
            
            if (!$apiKey) {
                $this->error('❌ API key not found');
                return 1;
            }

            $openRouterAdapter = new OpenRouterAdapter(
                $apiKey->api_key,
                $openRouterService->default_model,
                $openRouterService->config ?? []
            );

            $userService = new UserService();
            $groupService = new GroupService();
            $usageTrackingService = new UsageTrackingService();

            $messageProcessor = new MessageProcessorService(
                $baleAdapter,
                $openRouterAdapter,
                $userService,
                $groupService,
                $usageTrackingService
            );

            // Create test webhook payload with the ACTUAL structure from Bale
            $testWebhookPayload = [
                'update_id' => 166,
                'message' => [
                    'message_id' => 266,
                    'from' => [
                        'id' => 1038277246,
                        'is_bot' => false,
                        'first_name' => 'هادی قاسمیان',
                        'last_name' => null,
                        'username' => 'hadi_ghasemian'
                    ],
                    'date' => time(),
                    'chat' => [
                        'id' => 1038277246,
                        'type' => 'private',
                        'username' => 'hadi_ghasemian',
                        'first_name' => 'هادی قاسمیان'
                    ],
                    'text' => $testMessage
                ]
            ];

            $this->info("Testing with message: '{$testMessage}'");
            $this->info("Payload structure: " . json_encode($testWebhookPayload, JSON_PRETTY_PRINT));
            $this->newLine();

            // Test payload parsing
            $this->info('Testing payload parsing...');
            $parsedData = $baleAdapter->parseWebhookPayload($testWebhookPayload);
            $this->info('✅ Payload parsed successfully');
            $this->info('Parsed data: ' . json_encode($parsedData, JSON_PRETTY_PRINT));
            $this->newLine();

            // Process the webhook
            $this->info('Processing webhook...');
            $result = $messageProcessor->processMessage($testWebhookPayload);

            if ($result['success']) {
                $this->info('✅ Webhook processed successfully!');
                $this->info("AI Response: {$result['ai_response']}");
            } else {
                $this->error('❌ Webhook processing failed!');
                $this->error("Error: {$result['error']}");
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Test failed with exception:');
            $this->error($e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        $this->newLine();
        $this->info('🤖 Real webhook test completed.');

        return 0;
    }
}
