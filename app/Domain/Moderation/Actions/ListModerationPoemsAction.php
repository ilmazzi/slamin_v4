<?php

namespace Domain\Moderation\Actions;

use Domain\Moderation\DTOs\ListModerationPoemsData;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListModerationPoemsAction
{
    public function execute(ListModerationPoemsData $data): LengthAwarePaginator
    {
        $query = Poem::query()
            ->with(['user.profile', 'category.translations', 'type.translations'])
            ->withCount(['likes', 'bookmarks'])
            ->orderByRaw(
                "case when status = ? then 0 when status = ? then 1 else 2 end",
                [PoemStatus::PendingReview->value, PoemStatus::Rejected->value]
            )
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at');

        if ($data->status) {
            $query->where('status', $data->status);
        }

        if ($data->query) {
            $search = $data->query;
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhereHas('user.profile', fn ($q) => $q
                        ->where('nickname', 'like', "%{$search}%")
                        ->orWhere('display_name', 'like', "%{$search}%"));
            });
        }

        if ($data->poemCategoryId) {
            $query->where('poem_category_id', $data->poemCategoryId);
        }

        if ($data->poemTypeId) {
            $query->where('poem_type_id', $data->poemTypeId);
        }

        return $query->paginate($data->perPage);
    }
}
