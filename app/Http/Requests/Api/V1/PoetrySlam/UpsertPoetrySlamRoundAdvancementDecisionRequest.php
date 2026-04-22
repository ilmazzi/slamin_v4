<?php

namespace App\Http\Requests\Api\V1\PoetrySlam;

use Domain\PoetrySlam\Enums\PoetrySlamRoundAdvancementDecisionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertPoetrySlamRoundAdvancementDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'decision_status' => ['required', 'string', Rule::in(array_map(
                static fn (PoetrySlamRoundAdvancementDecisionStatus $status): string => $status->value,
                PoetrySlamRoundAdvancementDecisionStatus::cases(),
            ))],
            'note' => ['nullable', 'string'],
        ];
    }
}
