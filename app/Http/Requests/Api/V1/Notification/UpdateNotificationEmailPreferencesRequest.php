<?php

namespace App\Http\Requests\Api\V1\Notification;

use Domain\Notification\Enums\NotificationCategory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationEmailPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $rules = ['preferences' => ['required', 'array:'.implode(',', array_column(NotificationCategory::cases(), 'value'))]];

        foreach (NotificationCategory::cases() as $category) {
            $rules['preferences.'.$category->value] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    /**
     * @return array<string, bool>
     */
    public function preferences(): array
    {
        return array_map('boolval', $this->validated('preferences'));
    }
}
