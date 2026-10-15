<?php

namespace App\Http\Resources\Api\V1\Gig;

use Domain\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin User */
class GigUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->relationLoaded('profile') ? $this->profile : null;

        return [
            'id' => $this->id,
            'display_name' => $profile?->display_name ?? $profile?->nickname,
            'nickname' => $profile?->nickname,
            'avatar_url' => $profile?->avatar_path
                ? Storage::disk('public')->url($profile->avatar_path)
                : null,
        ];
    }
}
