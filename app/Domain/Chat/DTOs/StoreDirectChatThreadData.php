<?php

namespace Domain\Chat\DTOs;

use App\Http\Requests\Api\V1\Chat\StoreDirectChatThreadRequest;
use Domain\Profile\Models\Profile;
use Illuminate\Validation\ValidationException;

class StoreDirectChatThreadData
{
    public function __construct(
        public readonly int $targetUserId,
    ) {}

    public static function fromRequest(StoreDirectChatThreadRequest $request): self
    {
        if ($request->filled('user_id')) {
            return new self(
                targetUserId: (int) $request->integer('user_id'),
            );
        }

        $profile = Profile::query()
            ->where('nickname', $request->string('nickname')->toString())
            ->first();

        if ($profile === null) {
            throw ValidationException::withMessages([
                'nickname' => [__('chat.validation.unknown_nickname')],
            ]);
        }

        return new self(
            targetUserId: (int) $profile->user_id,
        );
    }
}
