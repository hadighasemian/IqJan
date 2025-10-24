<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'provider',
        'username',
        'first_name',
        'last_name',
        'phone',
        'language_code',
        'is_bot',
        'is_group',
        'group_id',
        'extra',
    ];

    protected function casts(): array
    {
        return [
            'is_bot' => 'boolean',
            'is_group' => 'boolean',
            'extra' => 'array',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->username ? "@{$this->username}" : $this->full_name;
    }
}
