<?php

namespace App\Http\Requests\Api\V1\Moderation;

use Domain\Event\Enums\EventStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListModerationEventsRequest extends FormRequest
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
                EventStatus::PendingReview->value,
                EventStatus::Rejected->value,
                EventStatus::Published->value,
                EventStatus::Draft->value,
            ])],
            'q' => ['nullable', 'string', 'max:255'],
            'event_category_id' => ['nullable', 'integer', 'exists:event_categories,id'],
            'festival_event_id' => ['nullable', 'integer', 'exists:events,id'],
        ];
    }
}
