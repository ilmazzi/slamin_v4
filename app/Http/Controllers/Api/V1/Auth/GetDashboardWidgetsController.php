<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Chat\Models\ChatMessage;
use Domain\Chat\Models\ChatThreadMember;
use Domain\Dashboard\Actions\ListRecentActivitiesAction;
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
    public function __construct(
        private readonly ListRecentActivitiesAction $recentActivities,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = 3;
        $page = max(1, (int) $request->integer('dashboard_activities_page', 1));
        $feed = $request->query('activities_feed') === 'mine' ? 'mine' : 'following';

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
                    ->from((new ChatThreadMember)->getTable().' as memberships')
                    ->whereColumn('memberships.chat_thread_id', 'chat_messages.chat_thread_id')
                    ->where('memberships.user_id', $user->id)
                    ->where(function ($subQuery) {
                        $subQuery
                            ->whereNull('memberships.last_read_at')
                            ->orWhereColumn('chat_messages.created_at', '>', 'memberships.last_read_at');
                    });
            })
            ->count();

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
            'recent_activities' => $feed === 'mine'
                ? $this->recentActivities->execute($user->id, $page, $perPage)
                : $this->recentActivities->executeFollowing($user->id, $page, $perPage),
            'recent_activities_feed' => $feed,
            'following_count' => $user->follows()->count(),
        ]);
    }
}
