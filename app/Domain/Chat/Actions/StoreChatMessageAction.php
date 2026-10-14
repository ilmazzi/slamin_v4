<?php

namespace Domain\Chat\Actions;

use App\Events\ChatMessageSent;
use Domain\Chat\DTOs\StoreChatMessageData;
use Domain\Chat\Enums\ChatMessageType;
use Domain\Chat\Models\ChatMessage;
use Domain\Chat\Models\ChatThread;
use Illuminate\Support\Facades\DB;

class StoreChatMessageAction
{
    public function __construct(
        private readonly NotifyChatMessageRecipientsAction $notifyChatMessageRecipientsAction,
        private readonly NotifyChatMessageReplyAction $notifyChatMessageReplyAction,
        private readonly StoreChatMessageAttachmentsAction $storeChatMessageAttachmentsAction,
    ) {}

    public function execute(ChatThread $thread, int $userId, StoreChatMessageData $data): ChatMessage
    {
        return DB::transaction(function () use ($thread, $userId, $data) {
            $replyToMessageId = null;

            if ($data->replyToMessageId !== null) {
                $replyToMessageId = $thread->messages()
                    ->whereKey($data->replyToMessageId)
                    ->value('id');
            }

            $message = $thread->messages()->create([
                'reply_to_message_id' => $replyToMessageId,
                'user_id' => $userId,
                'message_type' => ChatMessageType::Text,
                'content' => $data->content ?? '',
            ]);

            $this->storeChatMessageAttachmentsAction->execute($message, $data->attachments);

            $thread->update([
                'last_message_at' => $message->created_at,
            ]);

            $thread->memberRecords()
                ->where('user_id', $userId)
                ->update([
                    'last_read_at' => $message->created_at,
                ]);

            audit()->log(
                event: 'chat.message_sent',
                userId: $userId,
                auditable: $thread,
                newValues: [
                    'chat_message_id' => $message->id,
                    'chat_thread_id' => $thread->id,
                ],
                metadata: [
                    'source' => 'chat_message_store',
                ],
            );

            $this->notifyChatMessageRecipientsAction->execute($thread, $message);
            $this->notifyChatMessageReplyAction->execute($message);

            $message->load(['user.profile', 'replyTo.user.profile', 'reactions.user.profile', 'attachments', 'thread.memberRecords']);
            ChatMessageSent::dispatch($message);

            return $message;
        });
    }
}
