<?php

namespace App\Http\Requests\Api\V1\Newsletter;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeNewsletterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:255'],
            'locale' => ['nullable', 'string', 'max:10', 'regex:/^[a-z]{2}(?:-[A-Z]{2})?$/'],
        ];
    }
}
