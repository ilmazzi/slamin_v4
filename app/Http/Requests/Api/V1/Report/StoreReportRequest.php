<?php

namespace App\Http\Requests\Api\V1\Report;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:100'],
            'details' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
