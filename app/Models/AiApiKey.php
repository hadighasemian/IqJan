<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_service_id',
        'name',
        'api_key',
        'usage_count',
        'max_usage_per_day',
        'current_daily_usage',
        'last_usage_date',
        'last_used_at',
        'usage_stats',
        'is_active',
        'is_available',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'usage_stats' => 'array',
            'is_active' => 'boolean',
            'is_available' => 'boolean',
            'last_used_at' => 'datetime',
            'last_usage_date' => 'date',
        ];
    }

    protected $hidden = [
        'api_key',
    ];

    public function aiService(): BelongsTo
    {
        return $this->belongsTo(AiService::class);
    }

    public function incrementUsage(array $usageStats = []): void
    {
        $this->increment('usage_count');
        $this->increment('current_daily_usage');
        
        $this->update([
            'last_used_at' => now(),
            'last_usage_date' => now()->toDateString(),
            'usage_stats' => array_merge($this->usage_stats ?? [], $usageStats)
        ]);
    }

    public function resetDailyUsage(): void
    {
        $this->update([
            'current_daily_usage' => 0,
            'last_usage_date' => now()->toDateString()
        ]);
    }

    public function isUsageLimitReached(): bool
    {
        if (!$this->max_usage_per_day) {
            return false;
        }

        // Reset daily usage if it's a new day
        if ($this->last_usage_date && $this->last_usage_date->toDateString() !== now()->toDateString()) {
            $this->resetDailyUsage();
            return false;
        }

        return $this->current_daily_usage >= $this->max_usage_per_day;
    }
}
