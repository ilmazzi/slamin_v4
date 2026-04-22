<?php

namespace App\Http\Requests\Api\V1\Group;

use Illuminate\Foundation\Http\FormRequest;

class ListGroupsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'q' => ['nullable', 'string', 'max:255'],
        ];
    }
}
