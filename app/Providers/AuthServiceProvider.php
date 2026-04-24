<?php

namespace App\Providers;

use Domain\Comment\Policies\CommentPolicy;
use Domain\Chat\Policies\ChatThreadPolicy;
use Domain\Event\Policies\EventPolicy;
use Domain\Group\Policies\GroupAnnouncementPolicy;
use Domain\Group\Policies\GroupPolicy;
use Domain\Media\Policies\MediaItemPolicy;
use Domain\Media\Policies\MediaSnapPolicy;
use Domain\Article\Policies\ArticlePolicy;
use Domain\Chat\Models\ChatThread;
use Domain\Comment\Models\Comment;
use Domain\Event\Models\Event;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupAnnouncement;
use Domain\Media\Models\MediaItem;
use Domain\Media\Models\MediaSnap;
use Domain\Poetry\Models\Poem;
use Domain\Article\Models\Article;
use Domain\Poetry\Policies\PoemPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Poem::class => PoemPolicy::class,
        Article::class => ArticlePolicy::class,
        Comment::class => CommentPolicy::class,
        ChatThread::class => ChatThreadPolicy::class,
        Event::class => EventPolicy::class,
        Group::class => GroupPolicy::class,
        GroupAnnouncement::class => GroupAnnouncementPolicy::class,
        MediaItem::class => MediaItemPolicy::class,
        MediaSnap::class => MediaSnapPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
