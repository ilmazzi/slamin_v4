<?php

namespace Domain\Chat\DTOs;

use App\Http\Requests\Api\V1\Chat\StoreChatMessageRequest;
use Illuminate\Http\UploadedFile;

class StoreChatMessageData
{
    /**
     * @param  UploadedFile[]  $attachments
     */
    public function __construct(
        public readonly ?string $content,
        public readonly ?int $replyToMessageId,
        public readonly array $attachments,
    ) {
    }

    public static function fromRequest(StoreChatMessageRequest $request): self
    {
        return new self(
            content: $request->filled('content')
                ? trim($request->string('content')->toString())
                : null,
            replyToMessageId: $request->filled('reply_to_message_id') ? (int) $request->integer('reply_to_message_id') : null,
            attachments: $request->file('attachments', []),
        );
    }
}
