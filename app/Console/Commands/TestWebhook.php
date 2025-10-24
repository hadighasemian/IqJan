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

class TestWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:test-webhook {--message=Hello test message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test webhook processing with a simulated message';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🤖 Testing Webhook Processing...');
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

            // Create test webhook payload
            $testWebhookPayload = [
                'update' => [
                    'message' => [
                        'message_id' => rand(10000, 99999),
                        'from' => [
                            'id' => rand(100000, 999999),
                            'username' => 'test_user',
                            'first_name' => 'Test',
                            'last_name' => 'User'
                        ],
                        'chat' => [
                            'id' => rand(100000, 999999),
                            'type' => 'private',
                            'title' => null
                        ],
                        'text' => $testMessage,
                        'date' => time()
                    ]
                ]
            ];

            $this->info("Testing with message: '{$testMessage}'");
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
        $this->info('🤖 Webhook test completed.');

        return 0;
    }
}
