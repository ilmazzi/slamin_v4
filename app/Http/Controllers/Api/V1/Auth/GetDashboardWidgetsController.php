<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Audit\Models\AuditLog;
use Domain\Chat\Models\ChatMessage;
use Domain\Chat\Models\ChatThreadMember;
use Domain\Engagement\Models\Like;
use Domain\Event\Models\Event;
use Domain\Event\Models\EventAudienceInvite;
use Domain\Event\Models\EventParticipantInvite;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Models\GroupMember;
use Domain\Poetry\Models\Poem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetDashboardWidgetsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = 3;
        $page = max(1, (int) $request->integer('dashboard_activities_page', 1));

        $poemsCount = Poem::query()->where('user_id', $user->id)->count();
        $eventsCount = Event::query()->where('user_id', $user->id)->count();
        $likesCount = Like::query()->where('user_id', $user->id)->count();

        $groupsCount = GroupMember::query()
            ->where('user_id', $user->id)
            ->where('status', GroupMemberStatus::Active)
            ->count();

        $invitesCount = EventParticipantInvite::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->count()
            + EventAudienceInvite::query()
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->count();

        $unreadMessagesCount = ChatMessage::query()
            ->whereHas('thread.memberRecords', fn ($query) => $query->where('user_id', $user->id))
            ->where('user_id', '!=', $user->id)
            ->whereExists(function ($query) use ($user) {
                $query->selectRaw('1')
                    ->from((new ChatThreadMember())->getTable() . ' as memberships')
                    ->whereColumn('memberships.chat_thread_id', 'chat_messages.chat_thread_id')
                    ->where('memberships.user_id', $user->id)
                    ->where(function ($subQuery) {
                        $subQuery
                            ->whereNull('memberships.last_read_at')
                            ->orWhereColumn('chat_messages.created_at', '>', 'memberships.last_read_at');
                    });
            })
            ->count();

        $activities = AuditLog::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->paginate($perPage, ['*'], 'dashboard_activities_page', $page);

        return ApiResponse::success([
            'widgets' => [
                ['id' => 'poems', 'value' => $poemsCount],
                ['id' => 'events', 'value' => $eventsCount],
                ['id' => 'views', 'value' => 0],
                ['id' => 'likes', 'value' => $likesCount],
            ],
            'community_cards' => [
                [
                    'id' => 'friends',
                    'count' => 0,
                    'title' => __('home.dashboard_page.friends_title'),
                    'subtitle' => __('home.dashboard_page.friends_subtitle', ['count' => 0]),
                ],
                [
                    'id' => 'groups',
                    'count' => $groupsCount,
                    'title' => __('home.dashboard_page.groups_title'),
                    'subtitle' => __('home.dashboard_page.groups_subtitle', ['count' => $groupsCount]),
                ],
                [
                    'id' => 'invites',
                    'count' => $invitesCount,
                    'title' => __('home.dashboard_page.invites_title'),
                    'subtitle' => __('home.dashboard_page.invites_subtitle', ['count' => $invitesCount]),
                ],
                [
                    'id' => 'messages',
                    'count' => $unreadMessagesCount,
                    'title' => __('home.dashboard_page.messages_title'),
                    'subtitle' => __('home.dashboard_page.messages_subtitle', ['count' => $unreadMessagesCount]),
                ],
            ],
            'recent_activities' => [
                'total' => $activities->total(),
                'per_page' => $activities->perPage(),
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'items' => $activities->getCollection()->map(function (AuditLog $log) {
                    [$type, $action] = $this->resolveActivityTypeAndAction($log->event);
                    $label = $this->resolveActivityLabel($type, $action);
                    $title = $this->resolveActivityTitle($log);

                    return [
                        'id' => $log->id,
                        'type' => $type,
                        'action' => $action,
                        'label' => $label,
                        'title' => $title,
                        'time_ago' => $log->created_at?->diffForHumans() ?? __('home.dashboard_page.activity_now'),
                    ];
                })->values()->all(),
            ],
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveActivityTypeAndAction(string $event): array
    {
        $normalized = strtolower(trim($event));
        $action = 'created';

        if (str_contains($normalized, 'deleted')) {
            $action = 'deleted';
        } elseif (str_contains($normalized, 'updated')) {
            $action = 'updated';
        } elseif (str_contains($normalized, 'viewed')) {
            $action = 'viewed';
        } elseif (str_contains($normalized, 'liked')) {
            $action = 'liked';
        } elseif (str_contains($normalized, 'comment')) {
            $action = 'commented';
        } elseif (str_contains($normalized, 'invite')) {
            $action = 'invited';
        } elseif (str_contains($normalized, 'accept')) {
            $action = 'accepted';
        }

        $type = 'generic';
        if (str_contains($normalized, 'poem')) {
            $type = 'poem';
        } elseif (str_contains($normalized, 'article')) {
            $type = 'article';
        } elseif (str_contains($normalized, 'event')) {
            $type = 'event';
        } elseif (str_contains($normalized, 'media') || str_contains($normalized, 'video')) {
            $type = 'video';
        } elseif (str_contains($normalized, 'group')) {
            $type = 'group';
        } elseif (str_contains($normalized, 'gig')) {
            $type = 'gig';
        } elseif (str_contains($normalized, 'user') || str_contains($normalized, 'login')) {
            $type = 'user';
        }

        return [$type, $action];
    }

    private function resolveActivityLabel(string $type, string $action): string
    {
        $key = "home.dashboard_page.activity_{$action}_{$type}";
        $value = __($key);

        if ($value !== $key) {
            return $value;
        }

        $fallbackKey = "home.dashboard_page.activity_{$action}_generic";
        $fallback = __($fallbackKey);

        return $fallback !== $fallbackKey ? $fallback : __('home.dashboard_page.activity_generic');
    }

    private function resolveActivityTitle(AuditLog $log): string
    {
        $candidates = [
            data_get($log->new_values, 'title'),
            data_get($log->new_values, 'name'),
            data_get($log->old_values, 'title'),
            data_get($log->old_values, 'name'),
        ];

        foreach ($candidates as $value) {
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return __('home.dashboard_page.activity_no_title');
    }
}
