<?php

namespace Domain\Media\Actions;

use Domain\Media\DTOs\ListMediaData;
use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaType;
use Domain\Media\Enums\MediaVisibility;
use Domain\Media\Models\MediaItem;
use Domain\Media\Services\PeerTubeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListMediaAction
{
    public function __construct(
        private readonly PeerTubeService $peerTubeService,
        private readonly SyncPeerTubeVideoStatusAction $syncPeerTubeVideoStatusAction,
    ) {}

    public function execute(?int $viewerUserId, ListMediaData $data): LengthAwarePaginator
    {
        $this->syncPendingPublicVideos();

        $query = MediaItem::query()
            ->with(['user.profile'])
            ->withCount(['likes', 'comments', 'snaps'])
            ->withExists([
                'likes as is_liked' => fn ($builder) => $builder->where('user_id', $viewerUserId ?? 0),
            ])
            ->where('status', MediaStatus::Ready)
            ->where('visibility', MediaVisibility::Public);

        if ($data->type) {
            $query->where('type', $data->type);
        }

        if (filled($data->search)) {
            $term = '%'.addcslashes($data->search, '%_\\').'%';
            $query->where(function ($builder) use ($term): void {
                $builder
                    ->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        match ($data->sort) {
            'oldest' => $query->orderBy('published_at')->orderBy('id'),
            'popular' => $query->orderByDesc('likes_count')->orderByDesc('published_at')->orderByDesc('id'),
            default => $query->latest('published_at')->latest('id'),
        };

        return $query->paginate($data->perPage);
    }

    private function syncPendingPublicVideos(): void
    {
        if (! $this->peerTubeService->isConfigured()) {
            return;
        }

        $pending = MediaItem::query()
            ->where('type', MediaType::Video)
            ->where('status', MediaStatus::Processing)
            ->where('visibility', MediaVisibility::Public)
            ->where(function ($query): void {
                $query->whereNotNull('peertube_video_id')
                    ->orWhereNotNull('peertube_uuid');
            })
            ->orderBy('id')
            ->limit(15)
            ->get();

        foreach ($pending as $media) {
            try {
                $this->syncPeerTubeVideoStatusAction->execute($media);
            } catch (\Throwable) {
                // Listing must stay available even if PeerTube is down.
            }
        }
    }
}
