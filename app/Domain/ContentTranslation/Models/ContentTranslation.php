<?php

namespace Domain\ContentTranslation\Models;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentTranslation extends Model
{
    public const LOCALE_PATTERN = '[a-z]{2,3}(-[a-z0-9]{2,8})?';

    protected $fillable = [
        'translatable_type',
        'translatable_id',
        'locale',
        'title',
        'content',
        'summary',
        'user_id',
    ];

    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
