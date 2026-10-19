<?php

namespace App\Http\Requests\Api\V1\Group;

use Domain\Group\Enums\GroupChatAudience;
use Domain\Group\Enums\GroupVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
            'group_category_id' => ['required', 'integer', Rule::exists('group_categories', 'id')->where('is_active', true)],
            'visibility' => ['required', Rule::in(array_column(GroupVisibility::cases(), 'value'))],
            'is_listed' => ['nullable', 'boolean'],
            'auto_accept_events' => ['nullable', 'boolean'],
            'chat_audiences' => ['nullable', 'array'],
            'chat_audiences.*' => ['string', Rule::in(array_column(GroupChatAudience::cases(), 'value'))],
            'website_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
        ];
    }
}
