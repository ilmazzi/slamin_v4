<?php

namespace App\Http\Requests\Api\V1\Gig;

use Illuminate\Foundation\Http\FormRequest;

class OpenGigNegotiationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'deadline' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
