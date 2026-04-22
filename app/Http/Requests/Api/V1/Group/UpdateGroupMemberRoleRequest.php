<?php

namespace App\Http\Requests\Api\V1\Group;

use Domain\Group\Enums\GroupMemberRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGroupMemberRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(array_column(GroupMemberRole::cases(), 'value'))],
        ];
    }
}
