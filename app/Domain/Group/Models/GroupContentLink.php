<?php

namespace Domain\Group\Models;

use Domain\Article\Models\Article;
use Domain\Event\Models\Event;
use Domain\Group\Enums\GroupContentLinkSource;
use Domain\Group\Enums\GroupContentLinkStatus;
use Domain\Group\Enums\GroupContentType;
use Domain\Poetry\Models\Poem;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupContentLink extends Model
{
    protected $fillable = [
        'group_id',
        'content_type',
        'content_id',
        'status',
        'source',
        'proposed_by_user_id',
        'source_group_id',
        'handled_by_user_id',
        'handled_at',
    ];

    protected $casts = [
        'content_type' => GroupContentType::class,
        'status' => GroupContentLinkStatus::class,
        'source' => GroupContentLinkSource::class,
        'handled_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by_user_id');
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_user_id');
    }

    public function sourceGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'source_group_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'content_id');
    }

    public function poem(): BelongsTo
    {
        return $this->belongsTo(Poem::class, 'content_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'content_id');
    }
}
