<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'email' => $this->email,
            'email_verified' => $this->email_verified_at !== null,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'status' => $this->status,
            'account_type' => $this->account_type?->value ?? $this->account_type,
            'roles' => $this->roles->pluck('name')->values()->all(),
            'profile' => [
                'nickname' => $this->profile?->nickname,
                'display_name' => $this->profile?->display_name,
            ],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
