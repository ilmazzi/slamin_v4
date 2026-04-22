<?php

namespace Domain\PoetrySlam\DTOs;

use App\Http\Requests\Api\V1\PoetrySlam\UpdatePoetrySlamEntryRequest;
use Domain\PoetrySlam\Enums\PoetrySlamEntryStatus;

final class UpdatePoetrySlamEntryData
{
    public function __construct(
        public readonly bool $hasStatus,
        public readonly ?PoetrySlamEntryStatus $status,
        public readonly bool $hasNotes,
        public readonly ?string $notes,
        public readonly bool $hasPerformanceOrder,
        public readonly ?int $performanceOrder,
    ) {
    }

    public static function fromRequest(UpdatePoetrySlamEntryRequest $request): self
    {
        $statusValue = $request->validated('status');

        return new self(
            hasStatus: $request->exists('status'),
            status: $statusValue !== null ? PoetrySlamEntryStatus::from($statusValue) : null,
            hasNotes: $request->exists('notes'),
            notes: $request->validated('notes'),
            hasPerformanceOrder: $request->exists('performance_order'),
            performanceOrder: $request->validated('performance_order'),
        );
    }
}
