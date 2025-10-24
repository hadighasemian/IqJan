<?php

namespace App\Adapters\AiService;

use App\Adapters\AiService\Contracts\AiServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterAdapter implements AiServiceInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://openrouter.ai/api/v1';
    private string $defaultModel;
    private array $config;

    public function __construct(string $apiKey, string $defaultModel = 'openai/gpt-oss-20b:free', array $config = [])
    {
        $this->apiKey = $apiKey;
        $this->defaultModel = $defaultModel;
        $this->config = array_merge([
            'app_name' => 'IqJanBot',
            'app_url' => 'https://iq-jan.salam-raya.ir',
            'temperature' => 0.7,
            'max_tokens' => 1000,
            'timeout' => 30
        ], $config);
    }

    public function sendMessage(string $message, ?string $model = null, array $options = []): array
    {
        try {
            $model = $model ?: $this->defaultModel;
            
            $payload = [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $message
                    ]
                ],
                'temperature' => $options['temperature'] ?? $this->config['temperature'],
                'max_tokens' => $options['max_tokens'] ?? $this->config['max_tokens'],
            ];

            $headers = [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => $this->config['app_url'],
                'X-Title' => $this->config['app_name']
            ];

            $response = Http::timeout($this->config['timeout'])
                ->withHeaders($headers)
                ->post("{$this->baseUrl}/chat/completions", $payload);

            if (!$response->successful()) {
                $errorBody = $response->body();
                $errorData = json_decode($errorBody, true);
                
                Log::error('OpenRouter API call failed', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'payload' => $payload,
                    'headers' => $response->headers()
                ]);
                
                $errorMessage = 'Failed to get response from OpenRouter API';
                if (isset($errorData['error']['message'])) {
                    $errorMessage .= ': ' . $errorData['error']['message'];
                } elseif (isset($errorData['error'])) {
                    $errorMessage .= ': ' . $errorData['error'];
                }
                
                throw new \Exception($errorMessage);
            }

            $responseData = $response->json();
            
            return [
                'success' => true,
                'response' => $responseData['choices'][0]['message']['content'] ?? '',
                'usage' => $responseData['usage'] ?? [],
                'model' => $model,
                'raw_response' => $responseData
            ];
        } catch (\Exception $e) {
            Log::error('OpenRouter sendMessage error', [
                'error' => $e->getMessage(),
                'message' => $message,
                'model' => $model
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => 'متأسفانه در حال حاضر نمی‌توانم پاسخ دهم. لطفاً بعداً دوباره تلاش کنید.',
                'usage' => [],
                'model' => $model
            ];
        }
    }

    public function getAvailableModels(): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->get("{$this->baseUrl}/models");

            if (!$response->successful()) {
                Log::error('OpenRouter getModels failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            $data = $response->json();
            return $data['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('OpenRouter getAvailableModels error', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getName(): string
    {
        return 'openrouter';
    }

    public function isAvailable(): bool
    {
        try {
            // Simple health check by getting models
            $models = $this->getAvailableModels();
            return !empty($models);
        } catch (\Exception $e) {
            Log::error('OpenRouter availability check failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getUsageStats(): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->get("{$this->baseUrl}/auth/key");

            if (!$response->successful()) {
                return [];
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('OpenRouter getUsageStats error', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
