<?php

namespace App\Http\Requests\Api\V1\Media;

use Illuminate\Foundation\Http\FormRequest;

class GrantUserMediaEntitlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'media_feature_pack_id' => ['nullable', 'integer', 'exists:media_feature_packs,id'],
            'source' => ['sometimes', 'string', 'max:50'],
            'config' => ['nullable', 'array'],
            'config.photo_limit_bonus' => ['nullable', 'integer', 'min:0'],
            'config.video_limit_bonus' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
