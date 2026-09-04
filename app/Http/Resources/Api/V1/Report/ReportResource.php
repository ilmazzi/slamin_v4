<?php

namespace App\Http\Resources\Api\V1\Report;

use Domain\Article\Models\Article;
use Domain\Comment\Models\Comment;
use Domain\Media\Models\MediaItem;
use Domain\Poetry\Models\Poem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Domain\Report\Models\Report */
class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reason' => $this->reason,
            'details' => $this->details,
            'status' => $this->status?->value,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'resolution_note' => $this->resolution_note,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'reporter' => [
                'id' => $this->reporter?->id,
                'public_id' => $this->reporter?->public_id,
                'nickname' => $this->reporter?->profile?->nickname,
                'display_name' => $this->reporter?->profile?->display_name,
            ],
            'reviewed_by' => $this->reviewedBy ? [
                'id' => $this->reviewedBy->id,
                'public_id' => $this->reviewedBy->public_id,
                'nickname' => $this->reviewedBy->profile?->nickname,
                'display_name' => $this->reviewedBy->profile?->display_name,
            ] : null,
            'reportable' => $this->formatReportable(),
        ];
    }

    private function formatReportable(): ?array
    {
        $reportable = $this->reportable;

        return match (true) {
            $reportable instanceof Poem => [
                'type' => 'poem',
                'id' => $reportable->id,
                'slug' => $reportable->slug,
                'title' => $reportable->title,
                'status' => $reportable->status?->value,
            ],
            $reportable instanceof Article => [
                'type' => 'article',
                'id' => $reportable->id,
                'slug' => $reportable->slug,
                'title' => $reportable->title,
                'status' => $reportable->status?->value,
            ],
            $reportable instanceof Comment => [
                'type' => 'comment',
                'id' => $reportable->id,
                'content' => $reportable->content,
                'parent_id' => $reportable->parent_id,
            ],
            $reportable instanceof MediaItem => [
                'type' => 'media',
                'id' => $reportable->id,
                'title' => $reportable->title,
                'status' => $reportable->status?->value,
            ],
            default => null,
        };
    }
}
