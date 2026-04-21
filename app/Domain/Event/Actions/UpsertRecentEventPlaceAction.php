<?php

namespace Domain\Event\Actions;

use Domain\Event\Models\RecentEventPlace;

class UpsertRecentEventPlaceAction
{
    public function execute(int $userId, array $venue): void
    {
        RecentEventPlace::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'place_name' => $venue['place_name'],
                'country_code' => strtoupper($venue['country_code']),
                'city' => $venue['city'],
                'postal_code' => $venue['postal_code'] ?? null,
                'address_line' => $venue['address_line'] ?? null,
            ],
            [
                'latitude' => $venue['latitude'] ?? null,
                'longitude' => $venue['longitude'] ?? null,
                'last_used_at' => now(),
            ]
        );
    }
}
