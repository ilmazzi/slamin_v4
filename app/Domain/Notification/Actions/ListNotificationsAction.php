<?php

namespace Domain\Notification\Actions;

use Domain\Notification\Models\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListNotificationsAction
{
    public function execute(int $userId, int $perPage = 15, ?bool $unreadOnly = null): LengthAwarePaginator
    {
        $query = Notification::query()
            ->where('user_id', $userId)
            ->orderByRaw('read_at IS NULL DESC')
            ->orderByDesc('created_at');

        if ($unreadOnly === true) {
            $query->whereNull('read_at');
        }

        return $query->paginate($perPage);
    }
}
