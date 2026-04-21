<?php

namespace App\Http\Requests\Api\V1\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $profileId = $this->user()?->profile?->id;

        return [
            'nickname' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:30',
                'alpha_dash',
                Rule::unique('profiles', 'nickname')->ignore($profileId),
            ],
            'display_name' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'preferred_locale' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['it', 'en']),
            ],
        ];
    }
}
