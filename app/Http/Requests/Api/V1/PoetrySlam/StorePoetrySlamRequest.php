<?php

namespace App\Http\Requests\Api\V1\PoetrySlam;

use Illuminate\Foundation\Http\FormRequest;

class StorePoetrySlamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'rules_summary' => ['nullable', 'string'],
            'slots_limit' => ['nullable', 'integer', 'min:1'],
            'judging_mode' => ['nullable', 'string', 'max:255'],
            'settings' => ['nullable', 'array'],
        ];
    }
}