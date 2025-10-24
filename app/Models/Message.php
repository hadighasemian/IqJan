<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'group_id',
        'provider',
        'external_message_id',
        'message_type',
        'content',
        'file_id',
        'file_url',
        'ai_service',
        'ai_model',
        'ai_response',
        'ai_usage',
        'processing_error',
        'processed_at',
        'extra',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'ai_usage' => 'array',
            'extra' => 'array',
            'raw_payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function aiService(): BelongsTo
    {
        return $this->belongsTo(AiService::class, 'ai_service', 'name');
    }

    public function isProcessed(): bool
    {
        return !is_null($this->processed_at);
    }

    public function hasError(): bool
    {
        return !is_null($this->processing_error);
    }

    public function getResponseText(): string
    {
        return $this->ai_response ?? '';
    }
}
