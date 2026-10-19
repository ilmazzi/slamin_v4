<?php

namespace App\Http\Resources\Api\V1\Group;

use Domain\Group\Enums\GroupContentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Domain\Group\Models\GroupContentLink */
class GroupContentLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'group_id' => $this->group_id,
            'content_type' => $this->content_type?->value,
            'content_id' => $this->content_id,
            'status' => $this->status?->value,
            'source' => $this->source?->value,
            'event' => $this->content_type === GroupContentType::Event && $this->event ? [
                'id' => $this->event->id,
                'slug' => $this->event->slug,
                'title' => $this->event->title,
            ] : null,
            'poem' => $this->content_type === GroupContentType::Poem && $this->poem ? [
                'id' => $this->poem->id,
                'slug' => $this->poem->slug,
                'title' => $this->poem->title,
            ] : null,
            'article' => $this->content_type === GroupContentType::Article && $this->article ? [
                'id' => $this->article->id,
                'slug' => $this->article->slug,
                'title' => $this->article->title,
            ] : null,
            'group' => $this->whenLoaded('group', fn () => $this->group ? [
                'id' => $this->group->id,
                'slug' => $this->group->slug,
                'name' => $this->group->name,
            ] : null),
            'source_group' => $this->whenLoaded('sourceGroup', fn () => $this->sourceGroup ? [
                'id' => $this->sourceGroup->id,
                'slug' => $this->sourceGroup->slug,
                'name' => $this->sourceGroup->name,
            ] : null),
            'proposed_by' => $this->proposedBy ? [
                'id' => $this->proposedBy->id,
                'nickname' => $this->proposedBy->profile?->nickname,
                'display_name' => $this->proposedBy->profile?->display_name,
            ] : null,
            'created_at' => $this->created_at?->toISOString(),
            'handled_at' => $this->handled_at?->toISOString(),
        ];
    }
}
