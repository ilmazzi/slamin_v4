<?php

namespace App\Http\Requests\Api\V1\Article;

use App\Rules\RichTextContent;
use Domain\Article\Enums\ArticleStatus;
use Domain\Group\Support\GroupProposalRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        GroupProposalRules::normalize($this);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', new RichTextContent],
            'excerpt' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'language' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', Rule::in([ArticleStatus::Draft->value, ArticleStatus::Published->value])],
            'is_public' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'article_category_id' => [
                'nullable',
                'integer',
                Rule::exists('article_categories', 'id')->where('is_active', true),
            ],
            ...GroupProposalRules::rules(),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(fn ($validator) => GroupProposalRules::validate($this, $validator));
    }
}
