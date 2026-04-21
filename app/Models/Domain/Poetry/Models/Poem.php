<?php

namespace Domain\Poetry\Models;

use Domain\Poetry\Enums\PoemStatus;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Poem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'content',
        'description',
        'image_path',
        'language',
        'status',
        'is_public',
        'published_at',
    ];

    protected $casts = [
        'status' => PoemStatus::class,
        'is_public' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
