<?php

namespace App\Http\Requests\Api\V1\Moderation;

use Domain\Poetry\Enums\PoemStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListModerationPoemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'status' => ['nullable', Rule::in([
                PoemStatus::PendingReview->value,
                PoemStatus::Rejected->value,
                PoemStatus::Published->value,
                PoemStatus::Draft->value,
            ])],
            'q' => ['nullable', 'string', 'max:255'],
            'poem_category_id' => ['nullable', 'integer', 'exists:poem_categories,id'],
            'poem_type_id' => ['nullable', 'integer', 'exists:poem_types,id'],
        ];
    }
}
