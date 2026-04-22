<?php

namespace App\Http\Requests\Api\V1\Gig;

use Domain\Gig\Enums\TranslationCommentTarget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTranslationCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'comment_target' => ['required', 'string', Rule::in([
                TranslationCommentTarget::SourceText->value,
                TranslationCommentTarget::TranslatedText->value,
                TranslationCommentTarget::General->value,
            ])],
            'target_locator' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ];
    }
}
