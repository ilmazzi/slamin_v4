<?php

namespace App\Http\Requests\Api\V1\Forum;

use Domain\Forum\Enums\ForumPostStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateForumPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'body' => ['sometimes', 'required', 'string', 'max:20000'],
            'status' => ['sometimes', 'nullable', Rule::in([
                ForumPostStatus::Published->value,
                ForumPostStatus::Hidden->value,
            ])],
            'is_locked' => ['sometimes', 'nullable', 'boolean'],
            'is_pinned' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
