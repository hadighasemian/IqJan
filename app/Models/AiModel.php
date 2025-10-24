<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_service_id',
        'name',
        'display_name',
        'provider',
        'pricing_type',
        'cost_per_token',
        'max_tokens',
        'capabilities',
        'is_active',
        'is_default',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'cost_per_token' => 'decimal:8',
        ];
    }

    public function aiService(): BelongsTo
    {
        return $this->belongsTo(AiService::class);
    }

    public function isFree(): bool
    {
        return $this->pricing_type === 'free';
    }

    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities ?? []);
    }
}
