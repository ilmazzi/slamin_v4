<?php

namespace App\Http\Requests\Api\V1\Media;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaFeaturePackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'config' => ['sometimes', 'array'],
            'config.photo_limit_bonus' => ['nullable', 'integer', 'min:0'],
            'config.video_limit_bonus' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'price_amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
        ];
    }
}
