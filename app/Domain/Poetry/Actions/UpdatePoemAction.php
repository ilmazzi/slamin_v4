<?php

namespace Domain\Poetry\Actions;

use Domain\Admin\Actions\ResolveContentPublicationFlowAction;
use Domain\Poetry\DTOs\UpdatePoemData;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Domain\Shared\Enums\ContentType;
use Domain\Shared\Enums\PublicationFlow;
use Domain\Shared\Support\RichTextSanitizer;
use Illuminate\Support\Facades\Storage;

class UpdatePoemAction
{
    public function __construct(
        private readonly ResolveContentPublicationFlowAction $resolveContentPublicationFlowAction,
        private readonly RichTextSanitizer $richTextSanitizer,
    ) {}

    public function execute(Poem $poem, UpdatePoemData $data): Poem
    {
        $oldValues = [
            'title' => $poem->title,
            'content' => $poem->content,
            'description' => $poem->description,
            'poem_category_id' => $poem->poem_category_id,
            'poem_type_id' => $poem->poem_type_id,
            'image_path' => $poem->image_path,
            'language' => $poem->language,
            'status' => $poem->status?->value,
            'is_public' => $poem->is_public,
            'published_at' => $poem->published_at?->toISOString(),
        ];

        $status = $this->resolveTargetStatus($data->status);

        if ($status === PoemStatus::Published) {
            $publishedAt = $data->publishedAt
                ? now()->parse($data->publishedAt)
                : ($poem->published_at ?? now());
        } else {
            $publishedAt = null;
        }

        $imagePath = $poem->image_path;

        if ($data->removeImage && $imagePath) {
            Storage::disk('public')->delete($imagePath);
            $imagePath = null;
        }

        if ($data->image) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            $imagePath = $data->image->store('poems', 'public');
        }

        $poem->update([
            'title' => $data->title,
            'content' => $this->richTextSanitizer->sanitize($data->content),
            'description' => $data->description,
            'poem_category_id' => $data->poemCategoryId,
            'poem_type_id' => $data->poemTypeId,
            'image_path' => $imagePath,
            'language' => $data->language,
            'status' => $status,
            'is_public' => $data->isPublic,
            'published_at' => $publishedAt,
        ]);

        audit()->log(
            event: 'poem.updated',
            userId: $data->userId,
            auditable: $poem,
            oldValues: $oldValues,
            newValues: [
                'title' => $poem->title,
                'content' => $poem->content,
                'description' => $poem->description,
                'poem_category_id' => $poem->poem_category_id,
                'poem_type_id' => $poem->poem_type_id,
                'image_path' => $poem->image_path,
                'language' => $poem->language,
                'status' => $poem->status?->value,
                'is_public' => $poem->is_public,
                'published_at' => $poem->published_at?->toISOString(),
            ],
            metadata: [
                'source' => 'poem_update',
                'image_removed' => $data->removeImage,
                'image_replaced' => $data->image !== null,
            ],
        );

        return $poem->fresh(['user.profile', 'category.translations', 'type.translations']);
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
}
