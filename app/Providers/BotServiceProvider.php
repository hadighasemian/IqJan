<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Adapters\Messenger\BaleAdapter;
use App\Adapters\AiService\OpenRouterAdapter;
use App\Services\MessageProcessorService;
use App\Services\UserService;
use App\Services\GroupService;
use App\Services\UsageTrackingService;
use App\Models\AiService;
use App\Models\AiApiKey;

class BotServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Bale adapter
        $this->app->singleton(BaleAdapter::class, function ($app) {
            return new BaleAdapter(config('services.bale.token'));
        });

        // Register OpenRouter adapter
        $this->app->singleton(OpenRouterAdapter::class, function ($app) {
            $openRouterService = AiService::where('name', 'openrouter')->first();
            $apiKey = $openRouterService?->getAvailableApiKeys()->first();
            
            if (!$apiKey) {
                throw new \Exception('OpenRouter API key not configured');
            }

            return new OpenRouterAdapter(
                $apiKey->api_key,
                $openRouterService->default_model,
                $openRouterService->config ?? []
            );
        });

        // Register services
        $this->app->singleton(UserService::class);
        $this->app->singleton(GroupService::class);
        $this->app->singleton(UsageTrackingService::class);

        // Register message processor
        $this->app->singleton(MessageProcessorService::class, function ($app) {
            return new MessageProcessorService(
                $app->make(BaleAdapter::class),
                $app->make(OpenRouterAdapter::class),
                $app->make(UserService::class),
                $app->make(GroupService::class),
                $app->make(UsageTrackingService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
