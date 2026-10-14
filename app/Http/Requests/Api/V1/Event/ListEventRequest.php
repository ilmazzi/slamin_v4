<?php

namespace App\Http\Requests\Api\V1\Event;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'event_category_id' => ['nullable', 'integer', Rule::exists('event_categories', 'id')->where('is_active', true)],
            'festival_event_id' => ['nullable', 'integer', 'exists:events,id'],
            'nickname' => ['nullable', 'string', 'max:191'],
        ];
    }
}
