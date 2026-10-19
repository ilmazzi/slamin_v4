<?php

namespace App\Http\Requests\Api\V1\Group;

use Domain\Group\Enums\GroupChatAudience;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGroupChatThreadsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'audiences' => ['present', 'array'],
            'audiences.*' => ['string', Rule::in(array_column(GroupChatAudience::cases(), 'value'))],
        ];
    }
}
