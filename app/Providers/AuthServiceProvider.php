<?php

namespace App\Providers;

use App\Policies\CommentPolicy;
use App\Policies\ChatThreadPolicy;
use App\Policies\EventPolicy;
use App\Policies\GroupAnnouncementPolicy;
use App\Policies\GroupPolicy;
use App\Policies\MediaItemPolicy;
use App\Policies\MediaSnapPolicy;
use App\Policies\ArticlePolicy;
use Domain\Chat\Models\ChatThread;
use Domain\Comment\Models\Comment;
use Domain\Event\Models\Event;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupAnnouncement;
use Domain\Media\Models\MediaItem;
use Domain\Media\Models\MediaSnap;
use Domain\Poetry\Models\Poem;
use Domain\Article\Models\Article;
use App\Policies\PoemPolicy;
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
