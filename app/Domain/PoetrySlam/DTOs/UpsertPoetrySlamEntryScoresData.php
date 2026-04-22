<?php

namespace Domain\PoetrySlam\DTOs;

use App\Http\Requests\Api\V1\PoetrySlam\UpsertPoetrySlamEntryScoresRequest;

final class UpsertPoetrySlamEntryScoresData
{
    /**
     * @param array<int, array{judge_number:int, score:numeric}> $scores
     */
    public function __construct(
        public readonly array $scores,
    ) {
    }

    public static function fromRequest(UpsertPoetrySlamEntryScoresRequest $request): self
    {
        return new self(
            scores: $request->validated('scores'),
        );
    }
}
