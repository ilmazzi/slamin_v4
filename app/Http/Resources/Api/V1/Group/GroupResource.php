<?php

namespace App\Http\Resources\Api\V1\Group;

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
            'is_active' => $this->is_active,
            'website_url' => $this->website_url,
            'facebook_url' => $this->facebook_url,
            'instagram_url' => $this->instagram_url,
            'youtube_url' => $this->youtube_url,
            'active_members_count' => $this->active_members_count ?? $this->activeMembers->count(),
            'owners_count' => $this->owners_count ?? $this->members->where('role', 'owner')->where('status', 'active')->count(),
            'current_user_membership' => $currentMembership ? [
                'role' => $currentMembership->role?->value,
                'status' => $currentMembership->status?->value,
            ] : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
