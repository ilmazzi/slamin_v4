<?php

namespace App\Http\Requests\Api\V1\Admin;

use Domain\Shared\Enums\PublicationFlow;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateContentPublicationSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'publication_flow' => ['required', new Enum(PublicationFlow::class)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
