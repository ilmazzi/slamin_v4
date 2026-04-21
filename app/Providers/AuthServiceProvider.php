<?php

namespace App\Providers;

use App\Policies\CommentPolicy;
use App\Policies\EventPolicy;
use App\Policies\ArticlePolicy;
use Domain\Comment\Models\Comment;
use Domain\Event\Models\Event;
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
        Event::class => EventPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
