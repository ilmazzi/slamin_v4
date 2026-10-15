<?php

namespace Domain\Notification\Support;

use Domain\Notification\Enums\NotificationCategory;
use Domain\User\Models\User;
use Illuminate\Support\Facades\URL;

final class NotificationMailLinks
{
    private const COMMENTABLE_PATHS = [
        'poem' => 'poems',
        'event' => 'events',
        'article' => 'articles',
    ];

    public static function frontend(string $path = ''): string
    {
        return rtrim((string) config('app.frontend_url'), '/').'/'.ltrim($path, '/');
    }

    public static function preferences(): string
    {
        return self::frontend('profile/edit#notifications');
    }

    public static function unsubscribe(User $user, NotificationCategory $category): string
    {
        return URL::signedRoute('notifications.email.unsubscribe', [
            'user' => $user->public_id,
            'category' => $category->value,
        ]);
    }

    /**
     * Mirrors notificationLink() in frontend/src/components/notifications/notificationPresentation.js.
     */
    public static function forData(array $data): string
    {
        $path = match (true) {
            ! empty($data['translation_job_id']) => 'translation-jobs/'.$data['translation_job_id'],
            ! empty($data['negotiation_id']) => 'gig-negotiations/'.$data['negotiation_id'],
            ! empty($data['gig_id']) => 'gigs/'.$data['gig_id'],
            isset(self::COMMENTABLE_PATHS[$data['commentable_type'] ?? '']) && ! empty($data['commentable_slug']) => self::COMMENTABLE_PATHS[$data['commentable_type']].'/'.rawurlencode((string) $data['commentable_slug']),
            ($data['commentable_type'] ?? null) === 'media' && ! empty($data['commentable_id']) => 'media/'.$data['commentable_id'],
            ! empty($data['event_slug']) => 'events/'.rawurlencode((string) $data['event_slug']),
            ! empty($data['poem_slug']) => 'poems/'.rawurlencode((string) $data['poem_slug']),
            ! empty($data['article_slug']) => 'articles/'.rawurlencode((string) $data['article_slug']),
            ! empty($data['media_item_id']) => 'media/'.$data['media_item_id'],
            default => 'dashboard',
        };

        return self::frontend($path);
    }
}
