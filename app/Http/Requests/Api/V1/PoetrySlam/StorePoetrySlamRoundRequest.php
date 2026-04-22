<?php

namespace App\Http\Requests\Api\V1\PoetrySlam;

use Domain\PoetrySlam\Enums\PoetrySlamRoundAdvancementBasedOn;
use Domain\PoetrySlam\Enums\PoetrySlamRoundAdvancementMode;
use Domain\PoetrySlam\Enums\PoetrySlamRoundScoringType;
use Domain\PoetrySlam\Enums\PoetrySlamRoundStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePoetrySlamRoundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'round_number' => ['required', 'integer', 'min:1'],
            'name' => ['nullable', 'string', 'max:255'],
            'judges_count' => ['nullable', 'integer', 'min:1'],
            'scoring_type' => ['nullable', 'string', Rule::in(array_map(
                static fn (PoetrySlamRoundScoringType $type): string => $type->value,
                PoetrySlamRoundScoringType::cases(),
            ))],
            'advancement_mode' => ['nullable', 'string', Rule::in(array_map(
                static fn (PoetrySlamRoundAdvancementMode $mode): string => $mode->value,
                PoetrySlamRoundAdvancementMode::cases(),
            ))],
            'advancement_value' => [
                'nullable',
                'integer',
                'min:1',
                Rule::requiredIf(function (): bool {
                    return in_array($this->input('advancement_mode'), [
                        PoetrySlamRoundAdvancementMode::TopN->value,
                        PoetrySlamRoundAdvancementMode::TopPercentage->value,
                    ], true);
                }),
            ],
            'advancement_based_on' => ['nullable', 'string', Rule::in(array_map(
                static fn (PoetrySlamRoundAdvancementBasedOn $basedOn): string => $basedOn->value,
                PoetrySlamRoundAdvancementBasedOn::cases(),
            ))],
            'status' => ['nullable', 'string', Rule::in(array_map(
                static fn (PoetrySlamRoundStatus $status): string => $status->value,
                PoetrySlamRoundStatus::cases(),
            ))],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (
                $this->input('advancement_mode') === PoetrySlamRoundAdvancementMode::TopPercentage->value
                && $this->filled('advancement_value')
                && (int) $this->input('advancement_value') > 100
            ) {
                $validator->errors()->add('advancement_value', __('poetry_slam.validation.advancement_percentage_max'));
            }
        });
    }
}
