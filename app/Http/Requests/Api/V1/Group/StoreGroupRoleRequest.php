<?php

namespace App\Http\Requests\Api\V1\Group;

use Domain\Group\Enums\GroupMemberRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroupRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requested_role' => [
                'required',
                Rule::in([
                    GroupMemberRole::Admin->value,
                    GroupMemberRole::Editor->value,
                ]),
            ],
        ];
    }
}
