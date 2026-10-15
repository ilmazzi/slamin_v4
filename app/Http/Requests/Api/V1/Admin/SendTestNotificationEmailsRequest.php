<?php

namespace App\Http\Requests\Api\V1\Admin;

use Domain\Notification\Enums\NotificationCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendTestNotificationEmailsRequest extends FormRequest
{
    public const MAX_RECIPIENTS = 10;

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(NotificationCategory::class)],
            'user_ids' => ['required', 'array', 'min:1', 'max:'.self::MAX_RECIPIENTS],
            'user_ids.*' => ['required', 'string', 'distinct', Rule::exists('users', 'public_id')],
        ];
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::from($this->validated('category'));
    }

    /**
     * @return list<string>
     */
    public function userIds(): array
    {
        return $this->validated('user_ids');
    }
}
