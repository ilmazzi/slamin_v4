<?php

namespace App\Http\Requests\Api\V1\PoetrySlam;

use Domain\PoetrySlam\Enums\PoetrySlamEntrySource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePoetrySlamEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'entry_source' => ['required', 'string', Rule::in([
                PoetrySlamEntrySource::SiteUser->value,
                PoetrySlamEntrySource::External->value,
            ])],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                Rule::requiredIf(fn (): bool => $this->input('entry_source') === PoetrySlamEntrySource::SiteUser->value),
            ],
            'display_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn (): bool => $this->input('entry_source') === PoetrySlamEntrySource::External->value),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
