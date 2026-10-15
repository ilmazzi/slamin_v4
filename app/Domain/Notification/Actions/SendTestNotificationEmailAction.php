<?php

namespace Domain\Notification\Actions;

use App\Mail\Notifications\ChatMessageMail;
use App\Mail\Notifications\LikesSummaryMail;
use App\Mail\Notifications\NotificationCategoryMail;
use App\Mail\Notifications\NotificationMail;
use App\Mail\Notifications\WeeklyDigestMail;
use Domain\Chat\Models\ChatMessage;
use Domain\Chat\Models\ChatThreadMember;
use Domain\Notification\Enums\NotificationCategory;
use Domain\Notification\Models\Notification;
use Domain\Notification\Support\NotificationMailLinks;
use Domain\User\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Traits\Localizable;

class SendTestNotificationEmailAction
{
    use Localizable;

    private const RECENT_NOTIFICATIONS_SCANNED = 100;

    public function __construct(
        private readonly BuildWeeklyDigestContentAction $buildWeeklyDigestContent,
        private readonly BuildDigestNotificationsAction $buildDigestNotifications,
        private readonly BuildLikesSummaryAction $buildLikesSummary,
    ) {}

    /**
     * Sends right away and ignores the recipient's opt-outs: it is an explicit admin check.
     *
     * @return bool whether the email was built from the recipient's real data instead of a sample
     */
    public function execute(User $recipient, NotificationCategory $category, User $sender): bool
    {
        $recipient->loadMissing('profile');
        $sender->loadMissing('profile');

        [$mail, $usesRealData] = $this->withLocale(
            $recipient->preferredLocale(),
            fn () => $this->build($recipient, $category, $sender),
        );

        Mail::to($recipient)->send($mail);

        return $usesRealData;
    }

    /**
     * @return array{0: NotificationCategoryMail, 1: bool}
     */
    private function build(User $recipient, NotificationCategory $category, User $sender): array
    {
        return match ($category) {
            NotificationCategory::WeeklyDigest => $this->weeklyDigest($recipient),
            NotificationCategory::Likes => $this->likesSummary($recipient, $sender),
            NotificationCategory::ChatMessages => $this->chatMessage($recipient, $sender),
            default => $this->notification($recipient, $category),
        };
    }

    private function weeklyDigest(User $recipient): array
    {
        $since = now()->subWeek();
        $popular = $this->buildWeeklyDigestContent->execute($since);
        $notifications = $this->buildDigestNotifications->execute($recipient, $since);

        return [
            new WeeklyDigestMail($recipient, $popular, $notifications),
            $notifications !== [] || array_filter($popular) !== [],
        ];
    }

    private function likesSummary(User $recipient, User $sender): array
    {
        $summary = $this->buildLikesSummary->execute($recipient, now()->subDay(), now());

        if ($summary['total'] > 0) {
            return [new LikesSummaryMail($recipient, $summary['items'], $summary['total']), true];
        }

        $sample = [[
            'title' => __('notifications.mail.test.sample_content'),
            'url' => NotificationMailLinks::frontend('dashboard'),
            'count' => 1,
            'names' => [$this->displayName($sender)],
        ]];

        return [new LikesSummaryMail($recipient, $sample, 1), false];
    }

    private function chatMessage(User $recipient, User $sender): array
    {
        $message = ChatMessage::query()
            ->with(['user.profile', 'thread'])
            ->whereIn('chat_thread_id', ChatThreadMember::query()->where('user_id', $recipient->id)->select('chat_thread_id'))
            ->where('user_id', '!=', $recipient->id)
            ->latest('id')
            ->first();

        if ($message !== null) {
            return [new ChatMessageMail($recipient, $message), true];
        }

        $sample = (new ChatMessage(['content' => __('notifications.mail.test.chat_message')]))
            ->setRelation('user', $sender)
            ->setRelation('thread', null);

        return [new ChatMessageMail($recipient, $sample), false];
    }

    private function notification(User $recipient, NotificationCategory $category): array
    {
        $notification = Notification::query()
            ->where('user_id', $recipient->id)
            ->latest()
            ->limit(self::RECENT_NOTIFICATIONS_SCANNED)
            ->get()
            ->first(fn (Notification $notification) => NotificationCategory::fromType($notification->type) === $category);

        if ($notification !== null) {
            return [new NotificationMail($recipient, $category, $notification), true];
        }

        $sample = new Notification([
            'user_id' => $recipient->id,
            'type' => 'test',
            'title' => __('notifications.mail.test.title', [
                'category' => __('notifications.preferences.categories.'.$category->value.'.label'),
            ]),
            'message' => __('notifications.mail.test.message'),
            'data' => [],
        ]);

        return [new NotificationMail($recipient, $category, $sample), false];
    }

    private function displayName(User $user): string
    {
        return $user->profile?->display_name ?: ($user->profile?->nickname ?? $user->email);
    }
}
