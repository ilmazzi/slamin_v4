<?php

namespace App\Http\Requests\Api\V1\Gig;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTranslationCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'content' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
