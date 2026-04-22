<?php

namespace Domain\PoetrySlam\DTOs;

use App\Http\Requests\Api\V1\PoetrySlam\StorePoetrySlamRequest;

final class StorePoetrySlamData
{
    public function __construct(
        public readonly ?string $rulesSummary,
        public readonly ?int $slotsLimit,
        public readonly ?string $judgingMode,
        public readonly ?array $settings,
    ) {
    }

    public static function fromRequest(StorePoetrySlamRequest $request): self
    {
        return new self(
            rulesSummary: $request->validated('rules_summary'),
            slotsLimit: $request->validated('slots_limit'),
            judgingMode: $request->validated('judging_mode'),
            settings: $request->validated('settings'),
        );
    }
}