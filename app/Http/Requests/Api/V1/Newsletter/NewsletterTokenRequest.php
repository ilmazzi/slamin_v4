<?php

namespace App\Http\Requests\Api\V1\Newsletter;

use Illuminate\Foundation\Http\FormRequest;

class NewsletterTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:2048'],
        ];
    }
}
