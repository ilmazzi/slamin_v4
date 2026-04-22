<?php

namespace App\Http\Requests\Api\V1\PoetrySlam;

use Domain\PoetrySlam\Enums\PoetrySlamRoundAdvancementBasedOn;
use Domain\PoetrySlam\Enums\PoetrySlamRoundAdvancementMode;
use Domain\PoetrySlam\Enums\PoetrySlamRoundScoringType;
use Domain\PoetrySlam\Enums\PoetrySlamRoundStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePoetrySlamRoundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'round_number' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'judges_count' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'scoring_type' => ['sometimes', 'nullable', 'string', Rule::in(array_map(
                static fn (PoetrySlamRoundScoringType $type): string => $type->value,
                PoetrySlamRoundScoringType::cases(),
            ))],
            'advancement_mode' => ['sometimes', 'nullable', 'string', Rule::in(array_map(
                static fn (PoetrySlamRoundAdvancementMode $mode): string => $mode->value,
                PoetrySlamRoundAdvancementMode::cases(),
            ))],
            'advancement_value' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'advancement_based_on' => ['sometimes', 'nullable', 'string', Rule::in(array_map(
                static fn (PoetrySlamRoundAdvancementBasedOn $basedOn): string => $basedOn->value,
                PoetrySlamRoundAdvancementBasedOn::cases(),
            ))],
            'status' => ['sometimes', 'nullable', 'string', Rule::in(array_map(
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
