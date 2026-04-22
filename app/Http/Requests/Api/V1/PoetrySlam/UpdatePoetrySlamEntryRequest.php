<?php

namespace App\Http\Requests\Api\V1\PoetrySlam;

use Domain\PoetrySlam\Enums\PoetrySlamEntryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePoetrySlamEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', 'string', Rule::in(array_map(
                static fn (PoetrySlamEntryStatus $status): string => $status->value,
                PoetrySlamEntryStatus::cases(),
            ))],
            'notes' => ['sometimes', 'nullable', 'string'],
            'performance_order' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }
}
