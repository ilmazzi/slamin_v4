<?php

namespace App\Http\Requests\Api\V1\Moderation;

use Illuminate\Foundation\Http\FormRequest;

class RejectPoemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:2000'],
        ];
    }
}
