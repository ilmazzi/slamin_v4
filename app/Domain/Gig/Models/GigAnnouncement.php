<?php

namespace Domain\Gig\Models;

use Domain\Gig\Enums\GigAnnouncementStatus;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GigAnnouncement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'gig_id',
        'author_user_id',
        'message',
        'status',
        'published_at',
    ];

    protected $casts = [
        'status' => GigAnnouncementStatus::class,
        'published_at' => 'datetime',
    ];

    public function gig(): BelongsTo
    {
        return $this->belongsTo(Gig::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }
}
