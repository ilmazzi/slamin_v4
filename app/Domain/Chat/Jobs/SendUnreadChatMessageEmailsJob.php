<?php

namespace Domain\Chat\Jobs;

use App\Mail\Notifications\ChatMessageMail;
use Domain\Chat\Models\ChatMessage;
use Domain\Chat\Models\ChatThreadMember;
use Domain\Notification\Enums\NotificationCategory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendUnreadChatMessageEmailsJob implements ShouldQueue
{
    use Queueable;

    public const DELAY_MINUTES = 15;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $messageId,
    ) {}

    public function handle(): void
    {
        $message = ChatMessage::query()->with(['user.profile', 'thread'])->find($this->messageId);
        if ($message === null || $message->thread === null) {
            return;
        }

        $members = ChatThreadMember::query()
            ->with('user.profile')
            ->where('chat_thread_id', $message->chat_thread_id)
            ->where('user_id', '!=', $message->user_id)
            ->where(fn ($query) => $query->whereNull('last_read_at')->orWhere('last_read_at', '<', $message->created_at))
            ->where(fn ($query) => $query
                ->whereNull('last_emailed_at')
                ->orWhereColumn('last_emailed_at', '<', 'last_read_at'))
            ->get();

        foreach ($members as $member) {
            $recipient = $member->user;
            if ($recipient === null || ! $recipient->wantsNotificationEmail(NotificationCategory::ChatMessages)) {
                continue;
            }

            Mail::to($recipient)->send(new ChatMessageMail($recipient, $message));
            $member->forceFill(['last_emailed_at' => now()])->save();
        }
    }
}
