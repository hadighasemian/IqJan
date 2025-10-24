<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiService extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'api_url',
        'default_model',
        'config',
        'is_active',
        'is_available',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
            'is_available' => 'boolean',
        ];
    }

    public function models(): HasMany
    {
        return $this->hasMany(AiModel::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(AiApiKey::class);
    }

    public function getActiveModels()
    {
        return $this->models()->where('is_active', true)->orderBy('priority', 'desc');
    }

    public function getDefaultModel()
    {
        return $this->models()->where('is_default', true)->first();
    }

    public function getAvailableApiKeys()
    {
        return $this->apiKeys()->where('is_active', true)->where('is_available', true)->orderBy('priority', 'desc');
    }
}
