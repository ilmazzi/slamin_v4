<?php

namespace App\Http\Requests\Api\V1\Help;

use Illuminate\Foundation\Http\FormRequest;

class ListHelpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:120', 'alpha_dash'],
        ];
    }
}
