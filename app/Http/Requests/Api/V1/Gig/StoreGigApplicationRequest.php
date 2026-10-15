<?php

namespace App\Http\Requests\Api\V1\Gig;

use Illuminate\Foundation\Http\FormRequest;

class StoreGigApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'message' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
