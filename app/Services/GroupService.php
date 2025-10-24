<?php

namespace App\Services;

use App\Models\Group;

class GroupService
{
    public function findOrCreateGroup(array $parsedData): ?Group
    {
        // Only create group for non-private chats
        if ($parsedData['chat_type'] === 'private') {
            return null;
        }

        return Group::updateOrCreate(
            [
                'external_id' => $parsedData['chat_id'],
                'provider' => 'bale'
            ],
            [
                'title' => $parsedData['chat_title'] ?? 'Unknown Group',
                'type' => $parsedData['chat_type'],
                'extra' => [
                    'chat_id' => $parsedData['chat_id'],
                    'chat_type' => $parsedData['chat_type']
                ]
            ]
        );
    }

    public function findGroupByExternalId(string $externalId, string $provider = 'bale'): ?Group
    {
        return Group::where('external_id', $externalId)
            ->where('provider', $provider)
            ->first();
    }

    public function updateGroupLastActivity(Group $group): void
    {
        $group->touch();
    }
}
