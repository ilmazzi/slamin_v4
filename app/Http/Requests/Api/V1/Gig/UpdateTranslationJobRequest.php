<?php

namespace App\Http\Requests\Api\V1\Gig;

use App\Rules\RichTextContent;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTranslationJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'translated_content' => ['sometimes', 'nullable', 'string', new RichTextContent],
            'deadline' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
