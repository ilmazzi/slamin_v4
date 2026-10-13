<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:active,suspended,banned'],
            'account_type' => ['required', 'in:audience,poet_organizer'],
            'email_verified' => ['required', 'boolean'],
            'roles' => ['present', 'array'],
            'roles.*' => ['string', 'distinct', 'exists:roles,name'],
        ];
    }
}
