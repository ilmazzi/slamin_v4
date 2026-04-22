<?php

namespace App\Http\Requests\Api\V1\Media;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaCheckoutOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'media_feature_pack_id' => ['required', 'integer', 'exists:media_feature_packs,id'],
            'provider' => ['nullable', 'string', 'max:50'],
            'payload' => ['nullable', 'array'],
        ];
    }
}
