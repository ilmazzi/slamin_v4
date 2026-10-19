<?php

namespace Domain\Group\Actions;

use Domain\Group\DTOs\StoreGroupData;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Models\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreGroupAction
{
    public function __construct(
        private readonly UpdateGroupChatThreadsAction $updateGroupChatThreadsAction,
    ) {
    }

    public function execute(StoreGroupData $data): Group
    {
        return DB::transaction(function () use ($data) {
            $imagePath = $data->image?->store('groups', 'public');

            $group = Group::query()->create([
                'name' => $data->name,
                'slug' => $this->generateUniqueSlug($data->name),
                'description' => $data->description,
                'image_path' => $imagePath,
                'visibility' => $data->visibility,
                'is_listed' => $data->isListed,
                'auto_accept_events' => $data->autoAcceptEvents,
                'group_category_id' => $data->groupCategoryId,
                'website_url' => $data->websiteUrl,
                'facebook_url' => $data->facebookUrl,
                'instagram_url' => $data->instagramUrl,
                'youtube_url' => $data->youtubeUrl,
                'is_active' => true,
            ]);

            $group->members()->create([
                'user_id' => $data->userId,
                'role' => GroupMemberRole::Admin,
                'status' => GroupMemberStatus::Active,
                'joined_at' => now(),
                'handled_by_user_id' => $data->userId,
                'handled_at' => now(),
            ]);

            if ($data->chatAudiences !== []) {
                $this->updateGroupChatThreadsAction->execute($group, $data->chatAudiences, $data->userId);
            }

            audit()->log(
                event: 'group.created',
                userId: $data->userId,
                auditable: $group,
                newValues: [
                    'name' => $group->name,
                    'slug' => $group->slug,
                    'visibility' => $group->visibility?->value,
                ],
                metadata: [
                    'source' => 'group_store',
                ],
            );

            return $group->load(['members.user.profile', 'category.translations']);
        });
    }

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug !== '' ? $baseSlug : 'group';
        $originalSlug = $slug;
        $counter = 2;

        while (Group::query()->where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
