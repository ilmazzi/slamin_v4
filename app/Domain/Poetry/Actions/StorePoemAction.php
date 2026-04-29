<?php

namespace Domain\Poetry\Actions;

use Domain\Admin\Actions\ResolveContentPublicationFlowAction;
use Domain\Poetry\DTOs\StorePoemData;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Domain\Shared\Enums\ContentType;
use Domain\Shared\Enums\PublicationFlow;
use Illuminate\Support\Str;

class StorePoemAction
{
    public function __construct(
        private readonly ResolveContentPublicationFlowAction $resolveContentPublicationFlowAction,
    ) {
    }

    public function execute(StorePoemData $data): Poem
    {
        $imagePath = $data->image?->store('poems', 'public');

        $status = $this->resolveTargetStatus($data->status);

        if ($status === PoemStatus::Published) {
            $publishedAt = now();
        } else {
            $publishedAt = null;
        }

        $poem = Poem::create([
            'user_id' => $data->userId,
            'poem_category_id' => $data->poemCategoryId,
            'poem_type_id' => $data->poemTypeId,
            'title' => $data->title,
            'slug' => $this->generateUniqueSlug($data->title),
            'content' => $data->content,
            'description' => $data->description,
            'image_path' => $imagePath,
            'language' => $data->language,
            'status' => $status,
            'is_public' => $data->isPublic,
            'published_at' => $publishedAt,
        ]);

        audit()->log(
            event: 'poem.created',
            userId: $data->userId,
            auditable: $poem,
            newValues: [
                'title' => $poem->title,
                'slug' => $poem->slug,
                'poem_category_id' => $poem->poem_category_id,
                'poem_type_id' => $poem->poem_type_id,
                'language' => $poem->language,
                'status' => $poem->status?->value,
                'is_public' => $poem->is_public,
                'published_at' => $poem->published_at?->toISOString(),
                'image_path' => $poem->image_path,
            ],
            metadata: [
                'source' => 'poem_store',
            ],
        );

        return $poem->load(['user.profile', 'category.translations', 'type.translations']);
    }

    private function resolveTargetStatus(PoemStatus $requestedStatus): PoemStatus
    {
        if ($requestedStatus !== PoemStatus::Published) {
            return $requestedStatus;
        }

        $flow = $this->resolveContentPublicationFlowAction->execute(ContentType::Poem);

        return $flow === PublicationFlow::ManualReview
            ? PoemStatus::PendingReview
            : PoemStatus::Published;
    }

    private function generateUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug !== '' ? $baseSlug : 'poem';
        $originalSlug = $slug;
        $counter = 2;

        while (Poem::query()->where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
