<?php

namespace Domain\Group\Actions;

use Domain\Group\DTOs\UpdateGroupData;
use Domain\Group\Models\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateGroupAction
{
    public function execute(Group $group, UpdateGroupData $data): Group
    {
        return DB::transaction(function () use ($group, $data) {
            $oldValues = [
                'name' => $group->name,
                'description' => $group->description,
                'visibility' => $group->visibility?->value,
            ];

            $imagePath = $group->image_path;

            if ($data->removeImage && $imagePath) {
                Storage::disk('public')->delete($imagePath);
                $imagePath = null;
            }

            if ($data->image) {
                if ($imagePath) {
                    Storage::disk('public')->delete($imagePath);
                }

                $imagePath = $data->image->store('groups', 'public');
            }

            $group->update([
                'name' => $data->name,
                'description' => $data->description,
                'image_path' => $imagePath,
                'visibility' => $data->visibility,
                'website_url' => $data->websiteUrl,
                'facebook_url' => $data->facebookUrl,
                'instagram_url' => $data->instagramUrl,
                'youtube_url' => $data->youtubeUrl,
            ]);

            audit()->log(
                event: 'group.updated',
                userId: $data->userId,
                auditable: $group,
                oldValues: $oldValues,
                newValues: [
                    'name' => $group->name,
                    'description' => $group->description,
                    'visibility' => $group->visibility?->value,
                ],
                metadata: [
                    'source' => 'group_update',
                ],
            );

            return $group->fresh(['members.user.profile']);
        });
    }
}
