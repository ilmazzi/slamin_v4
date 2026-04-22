<?php

namespace Domain\PoetrySlam\DTOs;

use App\Http\Requests\Api\V1\PoetrySlam\UpdatePoetrySlamRoundRequest;
use Domain\PoetrySlam\Enums\PoetrySlamRoundAdvancementBasedOn;
use Domain\PoetrySlam\Enums\PoetrySlamRoundAdvancementMode;
use Domain\PoetrySlam\Enums\PoetrySlamRoundScoringType;
use Domain\PoetrySlam\Enums\PoetrySlamRoundStatus;

final class UpdatePoetrySlamRoundData
{
    public function __construct(
        public readonly bool $hasRoundNumber,
        public readonly ?int $roundNumber,
        public readonly bool $hasName,
        public readonly ?string $name,
        public readonly bool $hasJudgesCount,
        public readonly ?int $judgesCount,
        public readonly bool $hasScoringType,
        public readonly ?PoetrySlamRoundScoringType $scoringType,
        public readonly bool $hasAdvancementMode,
        public readonly ?PoetrySlamRoundAdvancementMode $advancementMode,
        public readonly bool $hasAdvancementValue,
        public readonly ?int $advancementValue,
        public readonly bool $hasAdvancementBasedOn,
        public readonly ?PoetrySlamRoundAdvancementBasedOn $advancementBasedOn,
        public readonly bool $hasStatus,
        public readonly ?PoetrySlamRoundStatus $status,
    ) {
    }

    public static function fromRequest(UpdatePoetrySlamRoundRequest $request): self
    {
        $statusValue = $request->validated('status');
        $scoringTypeValue = $request->validated('scoring_type');
        $advancementModeValue = $request->validated('advancement_mode');
        $advancementBasedOnValue = $request->validated('advancement_based_on');

        return new self(
            hasRoundNumber: $request->exists('round_number'),
            roundNumber: $request->validated('round_number'),
            hasName: $request->exists('name'),
            name: $request->validated('name'),
            hasJudgesCount: $request->exists('judges_count'),
            judgesCount: $request->validated('judges_count'),
            hasScoringType: $request->exists('scoring_type'),
            scoringType: $scoringTypeValue !== null ? PoetrySlamRoundScoringType::from($scoringTypeValue) : null,
            hasAdvancementMode: $request->exists('advancement_mode'),
            advancementMode: $advancementModeValue !== null ? PoetrySlamRoundAdvancementMode::from($advancementModeValue) : null,
            hasAdvancementValue: $request->exists('advancement_value'),
            advancementValue: $request->validated('advancement_value'),
            hasAdvancementBasedOn: $request->exists('advancement_based_on'),
            advancementBasedOn: $advancementBasedOnValue !== null ? PoetrySlamRoundAdvancementBasedOn::from($advancementBasedOnValue) : null,
            hasStatus: $request->exists('status'),
            status: $statusValue !== null ? PoetrySlamRoundStatus::from($statusValue) : null,
        );
    }
}
