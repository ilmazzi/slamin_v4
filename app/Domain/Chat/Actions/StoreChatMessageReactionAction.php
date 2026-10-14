<?php

namespace Domain\Chat\Actions;

use App\Events\ChatMessageUpdated;
use Domain\Chat\DTOs\ManageChatMessageReactionData;
use Domain\Chat\Models\ChatMessage;
use Domain\Chat\Models\ChatMessageReaction;
use Illuminate\Support\Facades\DB;

class StoreChatMessageReactionAction
{
    public function __construct(
        private readonly NotifyChatMessageReactionAction $notifyChatMessageReactionAction,
    ) {}

    public function execute(ChatMessage $message, int $userId, ManageChatMessageReactionData $data): ChatMessageReaction
    {
        return DB::transaction(function () use ($message, $userId, $data) {
            $reaction = ChatMessageReaction::query()->firstOrCreate([
                'chat_message_id' => $message->id,
                'user_id' => $userId,
                'emoji' => $data->emoji,
            ]);

            audit()->log(
                event: 'chat.message_reacted',
                userId: $userId,
                auditable: $message->thread,
                newValues: [
                    'chat_message_id' => $message->id,
                    'emoji' => $data->emoji,
                ],
                metadata: [
                    'source' => 'chat_message_reaction_store',
                ],
            );

            if ($reaction->wasRecentlyCreated) {
                $this->notifyChatMessageReactionAction->execute($reaction);
                ChatMessageUpdated::dispatch(
                    $message->load(['user.profile', 'replyTo.user.profile', 'reactions.user.profile', 'attachments']),
                );
            }

            return $reaction->load(['user.profile', 'message.user.profile']);
        });
    }
}
