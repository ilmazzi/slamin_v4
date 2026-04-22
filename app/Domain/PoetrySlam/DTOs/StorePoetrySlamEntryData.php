<?php

namespace Domain\PoetrySlam\DTOs;

use App\Http\Requests\Api\V1\PoetrySlam\StorePoetrySlamEntryRequest;
use Domain\PoetrySlam\Enums\PoetrySlamEntrySource;

final class StorePoetrySlamEntryData
{
    public function __construct(
        public readonly PoetrySlamEntrySource $entrySource,
        public readonly ?int $userId,
        public readonly ?string $displayName,
        public readonly ?string $email,
        public readonly ?string $notes,
    ) {
    }

    public static function fromRequest(StorePoetrySlamEntryRequest $request): self
    {
        return new self(
            entrySource: PoetrySlamEntrySource::from($request->validated('entry_source')),
            userId: $request->validated('user_id'),
            displayName: $request->validated('display_name'),
            email: $request->validated('email'),
            notes: $request->validated('notes'),
        );
    }
}
