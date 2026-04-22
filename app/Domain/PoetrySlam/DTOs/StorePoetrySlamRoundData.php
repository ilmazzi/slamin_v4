<?php

namespace Domain\PoetrySlam\DTOs;

use Domain\PoetrySlam\Enums\PoetrySlamRoundAdvancementBasedOn;
use Domain\PoetrySlam\Enums\PoetrySlamRoundAdvancementMode;
use Domain\PoetrySlam\Enums\PoetrySlamRoundScoringType;
use App\Http\Requests\Api\V1\PoetrySlam\StorePoetrySlamRoundRequest;
use Domain\PoetrySlam\Enums\PoetrySlamRoundStatus;

final class StorePoetrySlamRoundData
{
    public function __construct(
        public readonly int $roundNumber,
        public readonly ?string $name,
        public readonly int $judgesCount,
        public readonly PoetrySlamRoundScoringType $scoringType,
        public readonly PoetrySlamRoundAdvancementMode $advancementMode,
        public readonly ?int $advancementValue,
        public readonly PoetrySlamRoundAdvancementBasedOn $advancementBasedOn,
        public readonly PoetrySlamRoundStatus $status,
    ) {
    }

    public static function fromRequest(StorePoetrySlamRoundRequest $request): self
    {
        return new self(
            roundNumber: $request->validated('round_number'),
            name: $request->validated('name'),
            judgesCount: $request->validated('judges_count') ?? 5,
            scoringType: PoetrySlamRoundScoringType::from($request->validated('scoring_type') ?? PoetrySlamRoundScoringType::TrimmedSum->value),
            advancementMode: PoetrySlamRoundAdvancementMode::from($request->validated('advancement_mode') ?? PoetrySlamRoundAdvancementMode::AllEntries->value),
            advancementValue: $request->validated('advancement_value'),
            advancementBasedOn: PoetrySlamRoundAdvancementBasedOn::from($request->validated('advancement_based_on') ?? PoetrySlamRoundAdvancementBasedOn::RoundResult->value),
            status: PoetrySlamRoundStatus::from($request->validated('status') ?? PoetrySlamRoundStatus::Draft->value),
        );
    }
}
