<?php

namespace Domain\Group\Actions;

use Domain\Group\Models\GroupCategory;

class AdminDeleteGroupCategoryAction
{
    public function execute(GroupCategory $category, int $userId): bool
    {
        if ($category->groups()->exists()) {
            return false;
        }

        $oldValues = [
            'slug' => $category->slug,
            'is_active' => $category->is_active,
            'sort_order' => $category->sort_order,
        ];

        $category->delete();

        audit()->log(
            event: 'group_category.deleted',
            userId: $userId,
            auditable: $category,
            oldValues: $oldValues,
            metadata: [
                'source' => 'admin_group_category_delete',
            ],
        );

        return true;
    }
}
