<?php

namespace Domain\Chat\Actions;

use Domain\Chat\Models\ChatMessage;
use Illuminate\Http\UploadedFile;

class StoreChatMessageAttachmentsAction
{
    /**
     * @param  UploadedFile[]  $attachments
     */
    public function execute(ChatMessage $message, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            if (! $attachment instanceof UploadedFile) {
                continue;
            }

            $path = $attachment->store('chat/attachments', 'public');

            $message->attachments()->create([
                'disk' => 'public',
                'path' => $path,
                'original_name' => $attachment->getClientOriginalName(),
                'mime_type' => $attachment->getClientMimeType(),
                'size' => $attachment->getSize() ?? 0,
            ]);
        }
    }
}
