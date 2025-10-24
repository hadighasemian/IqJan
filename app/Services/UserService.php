<?php

namespace App\Services;

use App\Models\User;
use App\Models\Group;

class UserService
{
    public function findOrCreateUser(array $parsedData): User
    {
        return User::updateOrCreate(
            [
                'external_id' => $parsedData['user_id'],
                'provider' => 'bale'
            ],
            [
                'username' => $parsedData['username'],
                'first_name' => $parsedData['first_name'],
                'last_name' => $parsedData['last_name'],
                'language_code' => $parsedData['language_code'] ?? 'fa',
                'is_bot' => false,
                'is_group' => false,
                'extra' => [
                    'chat_type' => $parsedData['chat_type'],
                    'chat_id' => $parsedData['chat_id']
                ]
            ]
        );
    }

    public function findUserByExternalId(string $externalId, string $provider = 'bale'): ?User
    {
        return User::where('external_id', $externalId)
            ->where('provider', $provider)
            ->first();
    }

    public function updateUserLastActivity(User $user): void
    {
        $user->touch();
    }
}
