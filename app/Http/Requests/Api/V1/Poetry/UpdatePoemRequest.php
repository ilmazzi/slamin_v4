<?php

namespace App\Http\Requests\Api\V1\Poetry;

use App\Rules\RichTextContent;
use Domain\Group\Support\GroupProposalRules;
use Domain\Poetry\Enums\PoemStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePoemRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['remove_image', 'is_public'] as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $value = $this->input($field);

            if (! is_string($value)) {
                continue;
            }

            $normalized = strtolower(trim($value));

            if (in_array($normalized, ['true', '1', 'yes', 'on'], true)) {
                $this->merge([$field => true]);
            } else            if (in_array($normalized, ['false', '0', 'no', 'off', ''], true)) {
                $this->merge([$field => false]);
            }
        }

        GroupProposalRules::normalize($this);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', new RichTextContent],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
            'language' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', Rule::in([PoemStatus::Draft->value, PoemStatus::Published->value])],
            'is_public' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],

            'poem_category_id' => [
                'nullable',
                'integer',
                Rule::exists('poem_categories', 'id')->where('is_active', true),
            ],
            'poem_type_id' => [
                'nullable',
                'integer',
                Rule::exists('poem_types', 'id')->where('is_active', true),
            ],
            ...GroupProposalRules::rules(),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(fn ($validator) => GroupProposalRules::validate($this, $validator));
    }
}
