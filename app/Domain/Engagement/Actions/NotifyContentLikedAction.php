<?php

namespace Domain\Engagement\Actions;

use Domain\Article\Models\Article;
use Domain\Engagement\Models\Like;
use Domain\Event\Models\Event;
use Domain\Media\Models\MediaItem;
use Domain\Notification\Actions\CreateNotificationAction;
use Domain\Notification\Models\Notification;
use Domain\Poetry\Models\Poem;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class NotifyContentLikedAction
{
    public const TYPES = ['poem_liked', 'article_liked', 'event_liked', 'media_liked'];

    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(Like $like, Poem|Article|Event|MediaItem $content): void
    {
        if (! $like->wasRecentlyCreated || (int) $content->user_id === (int) $like->user_id) {
            return;
        }

        $contentType = $this->contentType($content);
        $type = $contentType.'_liked';

        $alreadyNotified = Notification::query()
            ->where('user_id', $content->user_id)
            ->where('type', $type)
            ->where('data->liker_id', $like->user_id)
            ->where('data->content_id', $content->id)
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        $profile = User::query()->with('profile')->find($like->user_id)?->profile;
        $likerName = $profile?->display_name ?: $profile?->nickname;
        $title = $content->title ?: null;

        $this->createNotificationAction->execute(
            userId: $content->user_id,
            type: $type,
            title: fn () => __('notifications.liked.title'),
            message: fn () => __('notifications.liked.message', [
                'name' => $likerName ?? __('notifications.someone'),
                'title' => $title ?? __('media.ui.untitled'),
            ]),
            data: [
                'liker_id' => $like->user_id,
                'liker_name' => $likerName,
                'content_type' => $contentType,
                'content_id' => $content->id,
                'content_title' => $title,
                ...$this->linkData($content),
            ],
        );
    }

    private function contentType(Model $content): string
    {
        return match (true) {
            $content instanceof Poem => 'poem',
            $content instanceof Article => 'article',
            $content instanceof Event => 'event',
            default => 'media',
        };
    }

    /**
     * @return array<string, int|string>
     */
    private function linkData(Poem|Article|Event|MediaItem $content): array
    {
        return match (true) {
            $content instanceof Poem => ['poem_slug' => $content->slug],
            $content instanceof Article => ['article_slug' => $content->slug],
            $content instanceof Event => ['event_slug' => $content->slug],
            default => ['media_item_id' => $content->id],
        };
    }
}
