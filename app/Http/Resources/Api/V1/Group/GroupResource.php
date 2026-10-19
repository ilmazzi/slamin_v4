<?php

namespace App\Http\Resources\Api\V1\Group;

use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \Domain\Group\Models\Group */
class GroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $disk = Storage::disk('public');
        $currentMembership = $request->user()
            ? $this->members->firstWhere('user_id', $request->user()->id)
            : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_path' => $this->image_path,
            'image_url' => $this->image_path ? $disk->url($this->image_path) : null,
            'visibility' => $this->visibility?->value,
            'is_listed' => (bool) $this->is_listed,
            'auto_accept_events' => (bool) $this->auto_accept_events,
            'group_category_id' => $this->group_category_id,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'slug' => $this->category->slug,
                'name' => $this->category->translatedName(),
            ] : null,
            'is_active' => $this->is_active,
            'website_url' => $this->website_url,
            'facebook_url' => $this->facebook_url,
            'instagram_url' => $this->instagram_url,
            'youtube_url' => $this->youtube_url,
            'active_members_count' => $this->active_members_count ?? $this->activeMembers->count(),
            'admins_count' => $this->admins_count ?? $this->members
                ->where('role', GroupMemberRole::Admin)
                ->where('status', GroupMemberStatus::Active)
                ->count(),
            'current_user_membership' => $currentMembership ? [
                'id' => $currentMembership->id,
                'role' => $currentMembership->role?->value,
                'requested_role' => $currentMembership->requested_role?->value,
                'status' => $currentMembership->status?->value,
            ] : null,
            'permissions' => $request->user() ? [
                'follow' => $request->user()->can('follow', $this->resource),
                'request_role' => $request->user()->can('requestRole', $this->resource),
                'leave' => $request->user()->can('leave', $this->resource),
                'invite' => $request->user()->can('invite', $this->resource),
                'update' => $request->user()->can('update', $this->resource),
                'publish_announcement' => $request->user()->can('publishAnnouncements', $this->resource),
                'manage_content_links' => $request->user()->can('manageContentLinks', $this->resource),
                'view_affiliations' => $request->user()->can('viewAffiliations', $this->resource),
                'manage_affiliations' => $request->user()->can('manageAffiliations', $this->resource),
                'manage_chat_threads' => $request->user()->can('manageChatThreads', $this->resource),
            ] : null,
            'chat_threads' => $this->when($this->relationLoaded('chatThreads'), fn () => $this->chatThreads
                ->map(fn ($thread) => [
                    'id' => $thread->id,
                    'audience' => $thread->audience?->value,
                    'is_enabled' => (bool) $thread->is_enabled,
                    'title' => $thread->title,
                ])
                ->values()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
