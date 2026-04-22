<?php

namespace App\Http\Requests\Api\V1\Group;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'is_pinned' => ['nullable', 'boolean'],
        ];
    }
}
