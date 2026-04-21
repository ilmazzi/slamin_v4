<?php

namespace App\Http\Requests\Api\V1\Moderation;

use Domain\Article\Enums\ArticleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListModerationArticlesRequest extends FormRequest
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
                ArticleStatus::PendingReview->value,
                ArticleStatus::Rejected->value,
                ArticleStatus::Published->value,
                ArticleStatus::Draft->value,
            ])],
            'q' => ['nullable', 'string', 'max:255'],
            'article_category_id' => ['nullable', 'integer', 'exists:article_categories,id'],
        ];
    }
}
