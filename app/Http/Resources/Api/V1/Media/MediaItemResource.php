<?php

namespace App\Http\Resources\Api\V1\Media;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Domain\Media\Models\MediaItem */
class MediaItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'title' => $this->title,
            'description' => $this->description,
            'visibility' => $this->visibility?->value,
            'status' => $this->status?->value,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'uploaded_at' => $this->uploaded_at?->toISOString(),
            'published_at' => $this->published_at?->toISOString(),
            'file_path' => $this->file_path,
            'file_url' => $this->resolvedFileUrl(),
            'width' => $this->width,
            'height' => $this->height,
            'peertube_video_id' => $this->peertube_video_id,
            'peertube_uuid' => $this->peertube_uuid,
            'peertube_short_uuid' => $this->peertube_short_uuid,
            'peertube_url' => $this->peertube_url,
            'peertube_embed_url' => $this->peertube_embed_url,
            'peertube_thumbnail_url' => $this->peertube_thumbnail_url,
            'duration_seconds' => $this->duration_seconds,
            'processing_status' => $this->processing_status,
            'upload_error' => $this->upload_error,
            'views_count' => (int) ($this->views_count ?? 0),
            'likes_count' => (int) ($this->likes_count ?? 0),
            'comments_count' => (int) ($this->comments_count ?? 0),
            'snaps_count' => (int) ($this->snaps_count ?? 0),
            'is_liked' => (bool) ($this->is_liked ?? false),
            'author' => [
                'id' => $this->user?->id,
                'public_id' => $this->user?->public_id,
                'nickname' => $this->user?->profile?->nickname,
                'display_name' => $this->user?->profile?->display_name,
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
