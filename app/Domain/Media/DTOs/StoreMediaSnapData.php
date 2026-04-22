<?php

namespace Domain\Media\DTOs;

use App\Http\Requests\Api\V1\Media\StoreMediaSnapRequest;
use Domain\Media\Enums\MediaSnapKind;

class StoreMediaSnapData
{
    public function __construct(
        public readonly int $timestampSeconds,
        public readonly MediaSnapKind $kind,
        public readonly string $comment,
    ) {
    }

    public static function fromRequest(StoreMediaSnapRequest $request): self
    {
        return new self(
            timestampSeconds: (int) $request->validated('timestamp_seconds'),
            kind: MediaSnapKind::from($request->validated('kind')),
            comment: $request->validated('comment'),
        );
    }
}
