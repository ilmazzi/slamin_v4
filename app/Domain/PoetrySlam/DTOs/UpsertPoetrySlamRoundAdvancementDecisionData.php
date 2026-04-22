<?php

namespace Domain\PoetrySlam\DTOs;

use App\Http\Requests\Api\V1\PoetrySlam\UpsertPoetrySlamRoundAdvancementDecisionRequest;
use Domain\PoetrySlam\Enums\PoetrySlamRoundAdvancementDecisionStatus;

final class UpsertPoetrySlamRoundAdvancementDecisionData
{
    public function __construct(
        public readonly PoetrySlamRoundAdvancementDecisionStatus $decisionStatus,
        public readonly ?string $note,
    ) {
    }

    public static function fromRequest(UpsertPoetrySlamRoundAdvancementDecisionRequest $request): self
    {
        return new self(
            decisionStatus: PoetrySlamRoundAdvancementDecisionStatus::from($request->validated('decision_status')),
            note: $request->validated('note'),
        );
    }
}
