<?php

namespace Domain\Media\DTOs;

use App\Http\Requests\Api\V1\Media\UpdateMediaSnapRequest;
use Domain\Media\Enums\MediaSnapKind;

class UpdateMediaSnapData
{
    public function __construct(
        public readonly ?int $timestampSeconds,
        public readonly ?MediaSnapKind $kind,
        public readonly ?string $comment,
    ) {
    }

    public static function fromRequest(UpdateMediaSnapRequest $request): self
    {
        return new self(
            timestampSeconds: $request->has('timestamp_seconds')
                ? (int) $request->validated('timestamp_seconds')
                : null,
            kind: $request->has('kind')
                ? MediaSnapKind::from($request->validated('kind'))
                : null,
            comment: $request->has('comment')
                ? $request->validated('comment')
                : null,
        );
    }
}
