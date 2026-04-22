<?php

namespace Domain\Chat\Actions;

use Domain\Chat\DTOs\ManageChatMessageReactionData;
use Domain\Chat\Models\ChatMessage;
use Illuminate\Support\Facades\DB;

class DeleteChatMessageReactionAction
{
    public function execute(ChatMessage $message, int $userId, ManageChatMessageReactionData $data): void
    {
        DB::transaction(function () use ($message, $userId, $data) {
            $message->reactions()
                ->where('user_id', $userId)
                ->where('emoji', $data->emoji)
                ->delete();

            audit()->log(
                event: 'chat.message_reaction_removed',
                userId: $userId,
                auditable: $message->thread,
                newValues: [
                    'chat_message_id' => $message->id,
                    'emoji' => $data->emoji,
                ],
                metadata: [
                    'source' => 'chat_message_reaction_delete',
                ],
            );
        });
    }
}
