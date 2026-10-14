<?php

namespace App\Events;

use App\Http\Resources\Api\V1\Chat\ChatMessageResource;
use Domain\Chat\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public bool $afterCommit = true;

    public function __construct(public ChatMessage $message)
    {
        $this->message->loadMissing([
            'thread.memberRecords',
            'user.profile',
            'replyTo.user.profile',
            'reactions.user.profile',
            'attachments',
        ]);
    }

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('chat.'.$this->message->chat_thread_id),
        ];

        foreach ($this->message->thread?->memberRecords ?? [] as $member) {
            if ((int) $member->user_id === (int) $this->message->user_id) {
                continue;
            }

            $channels[] = new PrivateChannel('chat.user.'.$member->user_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'thread_id' => $this->message->chat_thread_id,
            'message' => (new ChatMessageResource($this->message))->resolve(),
        ];
    }
}
