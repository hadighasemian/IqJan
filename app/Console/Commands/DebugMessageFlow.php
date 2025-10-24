<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Models\User;
use App\Models\Group;
use App\Models\AiService;
use App\Models\AiApiKey;
use App\Services\MessageProcessorService;
use App\Adapters\Messenger\BaleAdapter;
use App\Adapters\AiService\OpenRouterAdapter;
use App\Services\UserService;
use App\Services\GroupService;
use App\Services\UsageTrackingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DebugMessageFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:debug-flow {--message=Hello test message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug the complete message processing flow';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Debugging Message Processing Flow...');
        $this->newLine();

        $testMessage = $this->option('message');

        try {
            // Step 1: Check database setup
            $this->info('Step 1: Checking database setup...');
            
            $aiService = AiService::where('name', 'openrouter')->first();
            if (!$aiService) {
                $this->error('❌ AI service not found');
                return 1;
            }
            $this->info('✅ AI service found');

            $apiKey = $aiService->getAvailableApiKeys()->first();
            if (!$apiKey) {
                $this->error('❌ API key not found');
                return 1;
            }
            $this->info('✅ API key found');
            $this->newLine();

            // Step 2: Initialize services
            $this->info('Step 2: Initializing services...');
            
            $baleAdapter = new BaleAdapter(config('services.bale.token'));
            $openRouterAdapter = new OpenRouterAdapter(
                $apiKey->api_key,
                $aiService->default_model,
                $aiService->config ?? []
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
            
            $this->info('✅ All services initialized');
            $this->newLine();

            // Step 3: Test webhook payload parsing
            $this->info('Step 3: Testing webhook payload parsing...');
            
            $testWebhookPayload = [
                'update' => [
                    'message' => [
                        'message_id' => 12345,
                        'from' => [
                            'id' => 67890,
                            'username' => 'test_user',
                            'first_name' => 'Test',
                            'last_name' => 'User'
                        ],
                        'chat' => [
                            'id' => 67890,
                            'type' => 'private',
                            'title' => null
                        ],
                        'text' => $testMessage,
                        'date' => time()
                    ]
                ]
            ];

            $parsedData = $baleAdapter->parseWebhookPayload($testWebhookPayload);
            $this->info('✅ Webhook payload parsed successfully');
            $this->info('   Parsed data: ' . json_encode($parsedData, JSON_PRETTY_PRINT));
            $this->newLine();

            // Step 4: Test user creation
            $this->info('Step 4: Testing user creation...');
            
            $user = $userService->findOrCreateUser($parsedData);
            $this->info("✅ User created/found: {$user->display_name} (ID: {$user->id})");
            $this->newLine();

            // Step 5: Test group creation (if applicable)
            $this->info('Step 5: Testing group creation...');
            
            $group = $groupService->findOrCreateGroup($parsedData);
            if ($group) {
                $this->info("✅ Group created/found: {$group->title} (ID: {$group->id})");
            } else {
                $this->info('✅ No group needed (private chat)');
            }
            $this->newLine();

            // Step 6: Test message saving
            $this->info('Step 6: Testing message saving...');
            
            $message = Message::create([
                'user_id' => $user->id,
                'group_id' => $group?->id,
                'provider' => 'bale',
                'external_message_id' => $parsedData['message_id'],
                'message_type' => $parsedData['message_type'],
                'content' => $parsedData['text'],
                'ai_service' => 'openrouter',
                'raw_payload' => $parsedData['raw_payload']
            ]);
            
            $this->info("✅ Message saved (ID: {$message->id})");
            $this->newLine();

            // Step 7: Test AI processing
            $this->info('Step 7: Testing AI processing...');
            
            // Get default model
            $defaultModel = $aiService->getDefaultModel();
            $modelName = $defaultModel ? $defaultModel->name : 'openai/gpt-oss-20b:free';
            
            $this->info("Using model: {$modelName}");
            
            $aiResponse = $openRouterAdapter->sendMessage($message->content, $modelName);
            
            if ($aiResponse['success']) {
                $this->info('✅ AI processing successful');
                $this->info("   Response: {$aiResponse['response']}");
                $this->info("   Model: {$aiResponse['model']}");
                
                // Update message with AI response
                $message->update([
                    'ai_response' => $aiResponse['response'],
                    'ai_usage' => $aiResponse['usage'],
                    'ai_model' => $aiResponse['model'],
                    'processed_at' => now()
                ]);
                
                $this->info('✅ Message updated with AI response');
            } else {
                $this->error('❌ AI processing failed');
                $this->error("   Error: {$aiResponse['error']}");
                
                $message->update([
                    'processing_error' => $aiResponse['error'],
                    'processed_at' => now()
                ]);
            }
            $this->newLine();

            // Step 8: Test message editing (simulate)
            $this->info('Step 8: Testing message editing...');
            
            if ($aiResponse['success']) {
                $this->info('✅ Message editing would work (simulated)');
                $this->info("   Would edit message with: {$aiResponse['response']}");
            } else {
                $this->error('❌ Cannot edit message - AI processing failed');
            }
            $this->newLine();

            // Step 9: Check final message state
            $this->info('Step 9: Final message state...');
            
            $message->refresh();
            $this->info("   Message ID: {$message->id}");
            $this->info("   Content: {$message->content}");
            $this->info("   AI Response: " . ($message->ai_response ?: 'None'));
            $this->info("   Processed At: " . ($message->processed_at ?: 'Not processed'));
            $this->info("   Error: " . ($message->processing_error ?: 'None'));

        } catch (\Exception $e) {
            $this->error('❌ Debug failed with exception:');
            $this->error($e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        $this->newLine();
        $this->info('🔍 Message flow debug completed.');

        return 0;
    }
}
