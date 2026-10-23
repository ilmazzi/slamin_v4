<?php

namespace Domain\Dashboard\Actions;

use Domain\Article\Models\Article;
use Domain\Audit\Models\AuditLog;
use Domain\Comment\Models\Comment;
use Domain\Engagement\Models\Follow;
use Domain\Event\Models\Event;
use Domain\Forum\Models\ForumPost;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\GigApplication;
use Domain\Group\Models\Group;
use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaVisibility;
use Domain\Media\Models\MediaItem;
use Domain\Poetry\Models\Poem;
use Domain\Profile\Models\Profile;
use Domain\Profile\Support\ProfilePayload;
use Domain\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class ListRecentActivitiesAction
{
    /**
     * Audit events shown to the user, mapped to [type, action, label key].
     */
    private const ACTIVITIES = [
        'poem.created' => ['poem', 'created', 'poem_created'],
        'poem.updated' => ['poem', 'updated', 'poem_updated'],
        'poem.deleted' => ['poem', 'deleted', 'poem_deleted'],
        'poem.liked' => ['poem', 'liked', 'poem_liked'],
        'poem.bookmarked' => ['poem', 'bookmarked', 'poem_bookmarked'],
        'article.created' => ['article', 'created', 'article_created'],
        'article.updated' => ['article', 'updated', 'article_updated'],
        'article.deleted' => ['article', 'deleted', 'article_deleted'],
        'article.liked' => ['article', 'liked', 'article_liked'],
        'article.bookmarked' => ['article', 'bookmarked', 'article_bookmarked'],
        'event.created' => ['event', 'created', 'event_created'],
        'event.updated' => ['event', 'updated', 'event_updated'],
        'event.deleted' => ['event', 'deleted', 'event_deleted'],
        'event.liked' => ['event', 'liked', 'event_liked'],
        'event.bookmarked' => ['event', 'bookmarked', 'event_bookmarked'],
        'event_participant_invite.responded' => ['event', 'accepted', 'event_invite_responded'],
        'event_audience_invite.responded' => ['event', 'accepted', 'event_invite_responded'],
        'event_availability_option.finalized' => ['event', 'updated', 'event_date_finalized'],
        'comment.created' => ['comment', 'commented', 'comment_created'],
        'gig.created' => ['gig', 'created', 'gig_created'],
        'gig.updated' => ['gig', 'updated', 'gig_updated'],
        'gig.closed' => ['gig', 'updated', 'gig_closed'],
        'gig.application_created' => ['gig', 'created', 'gig_applied'],
        'gig.application_withdrawn' => ['gig', 'deleted', 'gig_withdrawn'],
        'group.created' => ['group', 'created', 'group_created'],
        'group.join_requested' => ['group', 'created', 'group_join_requested'],
        'group.left' => ['group', 'deleted', 'group_left'],
        'media.photo_uploaded' => ['photo', 'created', 'photo_uploaded'],
        'media.video_uploaded' => ['video', 'created', 'video_uploaded'],
        'media.liked' => ['media', 'liked', 'media_liked'],
        'media.deleted' => ['media', 'deleted', 'media_deleted'],
        'profile.updated' => ['user', 'updated', 'profile_updated'],
        'auth.registered' => ['user', 'created', 'registered'],
    ];

    /**
     * Subset shown on public profiles: only publications whose target is still publicly visible.
     */
    private const PUBLIC_ACTIVITIES = [
        'poem.created' => 'poems',
        'article.created' => 'articles',
        'event.created' => 'events',
        'media.photo_uploaded' => 'media',
        'media.video_uploaded' => 'media',
    ];

    /**
     * Changes shown to whoever follows an event.
     */
    private const FOLLOWED_EVENT_ACTIVITIES = [
        'event.updated',
        'event_availability_option.finalized',
    ];

    /**
     * @return array{total: int, per_page: int, current_page: int, last_page: int, items: list<array<string, mixed>>}
     */
    public function execute(int $userId, int $page, int $perPage = 3): array
    {
        $query = AuditLog::query()
            ->where('user_id', $userId)
            ->whereIn('event', array_keys(self::ACTIVITIES));

        return $this->paginate($query, $page, $perPage, 'home.dashboard_page.activity_labels');
    }

    /**
     * @param  list<string>  $sections  public profile sections the owner chose to show
     * @return array{total: int, per_page: int, current_page: int, last_page: int, items: list<array<string, mixed>>}
     */
    public function executePublic(int $userId, array $sections, int $page, int $perPage = 3): array
    {
        $events = array_keys(array_filter(
            self::PUBLIC_ACTIVITIES,
            fn (string $section) => in_array($section, $sections, true),
        ));

        $query = AuditLog::query()
            ->where('user_id', $userId)
            ->whereIn('event', $events);

        return $this->paginate($this->onlyPubliclyVisible($query), $page, $perPage, 'profile.public_activity_labels');
    }

    /**
     * Publications of the followed public profiles (within the sections each owner shows) and changes to the followed events.
     *
     * @return array{total: int, per_page: int, current_page: int, last_page: int, items: list<array<string, mixed>>}
     */
    public function executeFollowing(int $userId, int $page, int $perPage = 3): array
    {
        $follows = Follow::query()->where('user_id', $userId)->get(['followable_type', 'followable_id']);
        $eventIds = $follows->where('followable_type', (new Event)->getMorphClass())->pluck('followable_id')->all();
        $userIdsBySection = $this->followedUserIdsBySection(
            $follows->where('followable_type', (new User)->getMorphClass())->pluck('followable_id')->all(),
        );

        $query = AuditLog::query()
            ->where('user_id', '!=', $userId)
            ->where(function (Builder $query) use ($userIdsBySection, $eventIds) {
                $query->whereRaw('1 = 0');

                foreach ($userIdsBySection as $section => $userIds) {
                    $query->orWhere(fn (Builder $query) => $query
                        ->whereIn('user_id', $userIds)
                        ->whereIn('event', array_keys(self::PUBLIC_ACTIVITIES, $section, true)));
                }

                if ($eventIds !== []) {
                    $query->orWhere(fn (Builder $query) => $query
                        ->where('auditable_type', (new Event)->getMorphClass())
                        ->whereIn('auditable_id', $eventIds)
                        ->whereIn('event', self::FOLLOWED_EVENT_ACTIVITIES));
                }
            })
            ->with('user.profile:id,user_id,nickname,display_name');

        return $this->paginate($this->onlyPubliclyVisible($query), $page, $perPage, 'home.dashboard_page.following_activity_labels', withActor: true);
    }

    /**
     * @param  list<int>  $userIds
     * @return array<string, list<int>>
     */
    private function followedUserIdsBySection(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $bySection = [];
        $profiles = Profile::query()
            ->whereIn('user_id', $userIds)
            ->where('is_public', true)
            ->get(['user_id', 'visibility']);

        foreach ($profiles as $profile) {
            foreach (ProfilePayload::sections(ProfilePayload::visibility($profile->visibility)) as $section) {
                $bySection[$section][] = (int) $profile->user_id;
            }
        }

        return $bySection;
    }

    /**
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    private function onlyPubliclyVisible(Builder $query): Builder
    {
        return $query->whereHasMorph('auditable', [Poem::class, Article::class, Event::class, MediaItem::class], function (Builder $query, string $type) {
            if ($type === MediaItem::class) {
                $query->where('status', MediaStatus::Ready)->where('visibility', MediaVisibility::Public);

                return;
            }

            $query->where('status', 'published')->where('is_public', true);
        });
    }

    /**
     * @param  Builder<AuditLog>  $query
     * @return array{total: int, per_page: int, current_page: int, last_page: int, items: list<array<string, mixed>>}
     */
    private function paginate(Builder $query, int $page, int $perPage, string $labelNamespace, bool $withActor = false): array
    {
        /** @var LengthAwarePaginator $logs */
        $logs = $query
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage, ['*'], 'activities_page', $page);

        $targets = $this->resolveTargets($logs->getCollection());

        return [
            'total' => $logs->total(),
            'per_page' => $logs->perPage(),
            'current_page' => $logs->currentPage(),
            'last_page' => $logs->lastPage(),
            'items' => $logs->getCollection()->map(function (AuditLog $log) use ($targets, $labelNamespace, $withActor) {
                [$type, $action, $labelKey] = self::ACTIVITIES[$log->event];
                $target = $targets[$log->auditable_type.':'.$log->auditable_id] ?? null;
                $profile = $withActor ? $log->user?->profile : null;

                return [
                    'id' => $log->id,
                    'type' => $type,
                    'action' => $action,
                    'label' => __("{$labelNamespace}.{$labelKey}"),
                    'title' => $target['title'] ?? $this->titleFromValues($log),
                    'url' => $target['url'] ?? null,
                    'time_ago' => $log->created_at?->diffForHumans() ?? __('home.dashboard_page.activity_now'),
                    ...($profile ? ['actor' => [
                        'name' => $profile->display_name ?: $profile->nickname,
                        'url' => "/u/{$profile->nickname}",
                    ]] : []),
                ];
            })->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, AuditLog>  $logs
     * @return array<string, array{title: ?string, url: ?string}>
     */
    private function resolveTargets(Collection $logs): array
    {
        $refs = $logs
            ->filter(fn (AuditLog $log) => $log->auditable_type && $log->auditable_id)
            ->groupBy('auditable_type')
            ->map(fn (Collection $group) => $group->pluck('auditable_id')->unique()->values()->all());

        $aliases = [];

        $comments = $this->load(Comment::class, $refs->get(Comment::class, []));
        foreach ($comments as $comment) {
            $aliases[Comment::class.':'.$comment->id] = $comment->commentable_type.':'.$comment->commentable_id;
            $refs[$comment->commentable_type] = array_merge($refs->get($comment->commentable_type, []), [$comment->commentable_id]);
        }

        $applications = $this->load(GigApplication::class, $refs->get(GigApplication::class, []));
        foreach ($applications as $application) {
            $aliases[GigApplication::class.':'.$application->id] = Gig::class.':'.$application->gig_id;
            $refs[Gig::class] = array_merge($refs->get(Gig::class, []), [$application->gig_id]);
        }

        $targets = [];

        foreach ($this->load(Poem::class, $refs->get(Poem::class, [])) as $poem) {
            $targets[Poem::class.':'.$poem->id] = $this->target($poem->title, $poem->trashed() ? null : "/poems/{$poem->slug}");
        }

        foreach ($this->load(Article::class, $refs->get(Article::class, [])) as $article) {
            $targets[Article::class.':'.$article->id] = $this->target($article->title, $article->trashed() ? null : "/articles/{$article->slug}");
        }

        foreach ($this->load(Event::class, $refs->get(Event::class, [])) as $event) {
            $targets[Event::class.':'.$event->id] = $this->target($event->title, $event->trashed() ? null : '/events/'.($event->slug ?: $event->id));
        }

        foreach ($this->load(Gig::class, $refs->get(Gig::class, []), ['giggable']) as $gig) {
            $title = $gig->giggable?->getAttribute('title');
            $targets[Gig::class.':'.$gig->id] = $this->target(is_string($title) ? $title : null, $gig->trashed() ? null : "/gigs/{$gig->id}");
        }

        foreach ($this->load(Group::class, $refs->get(Group::class, [])) as $group) {
            $targets[Group::class.':'.$group->id] = $this->target($group->name, null);
        }

        foreach ($this->load(MediaItem::class, $refs->get(MediaItem::class, [])) as $media) {
            $targets[MediaItem::class.':'.$media->id] = $this->target($media->title, $media->trashed() ? null : "/media/{$media->id}");
        }

        foreach ($this->load(ForumPost::class, $refs->get(ForumPost::class, []), ['category']) as $post) {
            $categorySlug = $post->category?->slug;
            $url = ($post->trashed() || ! $categorySlug)
                ? null
                : "/forum/{$categorySlug}/{$post->slug}";
            $targets[ForumPost::class.':'.$post->id] = $this->target($post->title, $url);
        }

        foreach ($aliases as $alias => $key) {
            if (isset($targets[$key])) {
                $targets[$alias] = $targets[$key];
            }
        }

        return $targets;
    }

    /**
     * @param  class-string<Model>  $class
     * @param  array<int, int|string>  $ids
     * @param  list<string>  $with
     * @return Collection<int, Model>
     */
    private function load(string $class, array $ids, array $with = []): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $query = $class::query()->with($with)->whereKey(array_values(array_unique($ids)));

        if (in_array(SoftDeletes::class, class_uses_recursive($class), true)) {
            $query->withTrashed();
        }

        return $query->get();
    }

    /**
     * @return array{title: ?string, url: ?string}
     */
    private function target(?string $title, ?string $url): array
    {
        $title = is_string($title) && trim($title) !== '' ? $title : null;

        return ['title' => $title, 'url' => $url];
    }

    private function titleFromValues(AuditLog $log): ?string
    {
        foreach (['new_values.title', 'new_values.name', 'old_values.title', 'old_values.name'] as $path) {
            $value = data_get($log, $path);
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }
}
