<?php

namespace Domain\Media\Models;

use Domain\Comment\Models\Comment;
use Domain\Engagement\Models\Like;
use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaType;
use Domain\Media\Enums\MediaVisibility;
use Domain\Report\Models\Report;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MediaItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'description',
        'visibility',
        'status',
        'mime_type',
        'file_size',
        'uploaded_at',
        'published_at',
        'file_path',
        'file_url',
        'width',
        'height',
        'peertube_video_id',
        'peertube_uuid',
        'peertube_short_uuid',
        'peertube_url',
        'peertube_embed_url',
        'peertube_thumbnail_url',
        'duration_seconds',
        'processing_status',
        'upload_error',
    ];

    protected $casts = [
        'type' => MediaType::class,
        'visibility' => MediaVisibility::class,
        'status' => MediaStatus::class,
        'uploaded_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function snaps(): HasMany
    {
        return $this->hasMany(MediaSnap::class)->orderBy('timestamp_seconds')->orderBy('id');
    }

    public function resolvedFileUrl(): ?string
    {
        if ($this->type === MediaType::Photo && $this->file_path) {
            return Storage::disk('public')->url($this->file_path);
        }

        return $this->file_url ?: $this->peertube_url;
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === MediaStatus::Ready
            && $this->visibility === MediaVisibility::Public;
    }
}
