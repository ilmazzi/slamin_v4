<?php

namespace Domain\Event\Queries;

use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\EventVenueDetail;
use Domain\Event\Models\RecentEventPlace;
use Illuminate\Support\Collection;

class SearchEventPlacesQuery
{
    public function execute(int $userId, string $query, int $limit = 8): Collection
    {
        $term = trim($query);

        if (mb_strlen($term) < 2) {
            return collect();
        }

        $like = '%'.$this->escapeLike($term).'%';
        $prefix = $this->escapeLike($term).'%';

        $userRecent = RecentEventPlace::query()
            ->where('user_id', $userId)
            ->where(function ($places) use ($like): void {
                $places
                    ->where('place_name', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('address_line', 'like', $like);
            })
            ->orderByDesc('last_used_at')
            ->limit($limit)
            ->get();

        $venues = EventVenueDetail::query()
            ->select([
                'place_name',
                'country_code',
                'city',
                'postal_code',
                'address_line',
                'latitude',
                'longitude',
            ])
            ->where(function ($places) use ($like): void {
                $places
                    ->where('place_name', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('address_line', 'like', $like);
            })
            ->whereHas('event', function ($events) use ($userId): void {
                $events->where(function ($visible) use ($userId): void {
                    $visible
                        ->where('user_id', $userId)
                        ->orWhere(function ($public) {
                            $public
                                ->where('is_public', true)
                                ->where('status', EventStatus::Published->value);
                        });
                });
            })
            ->orderByRaw('CASE WHEN place_name LIKE ? THEN 0 ELSE 1 END', [$prefix])
            ->orderBy('place_name')
            ->limit(40)
            ->get();

        $merged = collect();
        $seen = [];

        foreach ($userRecent->concat($venues) as $place) {
            if ($merged->count() >= $limit) {
                break;
            }

            $key = mb_strtolower(implode('|', [
                trim((string) $place->place_name),
                trim((string) $place->city),
                trim((string) ($place->address_line ?? '')),
                strtoupper((string) $place->country_code),
            ]));

            if ($key === '|||' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $merged->push($place);
        }

        return $merged->values();
    }

    private function escapeLike(string $value): string
    {
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\%', '\_'],
            $value,
        );
    }
}
