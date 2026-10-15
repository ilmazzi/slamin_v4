<?php

namespace Domain\Notification\Actions;

use App\Events\NotificationCreated;
use Closure;
use Domain\Notification\Enums\NotificationCategory;
use Domain\Notification\Jobs\SendNotificationEmailJob;
use Domain\Notification\Models\Notification;
use Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Traits\Localizable;

class CreateNotificationAction
{
    use Localizable;

    /**
     * Pass title and message as closures so they are translated in the recipient's language,
     * not in the language of whoever triggered the notification.
     *
     * @param  string|Closure(): string  $title
     * @param  string|Closure(): string  $message
     */
    public function execute(
        int $userId,
        string $type,
        string|Closure $title,
        string|Closure $message,
        array $data = [],
    ): Notification {
        [$title, $message] = $this->withLocale(
            $this->recipientLocale($userId, $title, $message),
            fn (): array => [value($title), value($message)],
        );

        $notification = Notification::query()->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        // A realtime outage must never roll back or fail the action that produced the notification.
        DB::afterCommit(fn () => rescue(fn () => NotificationCreated::dispatch($notification)));

        if (NotificationCategory::fromType($type)?->isMailedImmediately()) {
            SendNotificationEmailJob::dispatch($notification->id)->afterCommit();
        }

        return $notification;
    }

    private function recipientLocale(int $userId, string|Closure $title, string|Closure $message): ?string
    {
        if (! $title instanceof Closure && ! $message instanceof Closure) {
            return null;
        }

        return User::query()->with('profile:id,user_id,preferred_locale')->find($userId, ['id'])?->preferredLocale();
    }
}
