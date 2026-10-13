<?php

namespace App\Http\Requests\Api\V1\Event;

use Domain\Event\Models\EventCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var EventCategory $eventCategory */
        $eventCategory = $this->route('eventCategory');
        $slugRules = [
            'required',
            'string',
            'max:120',
            'alpha_dash',
            Rule::unique('event_categories', 'slug')->ignore($eventCategory->id),
        ];

        if ($eventCategory->isSystemCategory()) {
            $slugRules[] = Rule::in([$eventCategory->slug]);
        }

        return [
            'slug' => $slugRules,
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.locale' => ['required', 'string', 'max:10', 'distinct'],
            'translations.*.name' => ['required', 'string', 'max:100'],
        ];
    }
}
