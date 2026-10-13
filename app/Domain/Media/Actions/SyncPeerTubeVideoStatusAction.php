<?php

namespace Domain\Media\Actions;

use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaType;
use Domain\Media\Models\MediaItem;
use Domain\Media\Services\PeerTubeService;
use Illuminate\Validation\ValidationException;

class SyncPeerTubeVideoStatusAction
{
    public function __construct(
        private readonly PeerTubeService $peerTubeService,
        private readonly NotifyMediaVideoReadyAction $notifyMediaVideoReadyAction,
        private readonly NotifyMediaVideoFailedAction $notifyMediaVideoFailedAction,
    ) {}

    public function execute(MediaItem $media): MediaItem
    {
        if ($media->type !== MediaType::Video) {
            throw ValidationException::withMessages([
                'media' => [__('media.validation.video_status_only_for_videos')],
            ]);
        }

        if (! $this->peerTubeService->isConfigured()) {
            throw ValidationException::withMessages([
                'media' => [__('media.validation.peertube_not_configured')],
            ]);
        }

        if ($media->status === MediaStatus::Ready || $media->status === MediaStatus::Failed) {
            return $this->loadMedia($media);
        }

        $identifier = $media->peertube_video_id ?: $media->peertube_uuid;

        if (! $identifier) {
            return $this->loadMedia($media);
        }

        $details = $this->peerTubeService->fetchVideoDetails($identifier);

        if (! $details) {
            return $this->loadMedia($media);
        }

        $state = $this->resolveStateFromPeerTubePayload($details);
        $previousStatus = $media->status;

        $media->fill([
            'duration_seconds' => $details['duration'] ?? $media->duration_seconds,
            'peertube_thumbnail_url' => $this->peerTubeService->resolveThumbnailUrl($details)
                ?? $media->peertube_thumbnail_url,
            'peertube_url' => $this->peerTubeService->buildWatchUrl(
                $details['shortUUID'] ?? $media->peertube_short_uuid,
                $details['uuid'] ?? $media->peertube_uuid,
            ) ?? $media->peertube_url,
            'peertube_embed_url' => $this->peerTubeService->buildEmbedUrl(
                $details['shortUUID'] ?? $media->peertube_short_uuid,
                $details['uuid'] ?? $media->peertube_uuid,
            ) ?? $media->peertube_embed_url,
            'processing_status' => $state['processing_status'],
        ]);

        if ($state['status'] !== null) {
            $media->status = $state['status'];
            $media->upload_error = $state['upload_error'];

            if ($state['status'] === MediaStatus::Ready && $media->published_at === null) {
                $media->published_at = now();
            }
        }

        $media->save();

        if ($previousStatus !== $media->status) {
            if ($media->status === MediaStatus::Ready) {
                audit()->log(
                    event: 'media.video_ready',
                    userId: $media->user_id,
                    auditable: $media,
                    newValues: [
                        'status' => $media->status?->value,
                        'processing_status' => $media->processing_status,
                    ],
                    metadata: [
                        'source' => 'media_video_sync',
                    ],
                );

                $this->notifyMediaVideoReadyAction->execute($media);
            }

            if ($media->status === MediaStatus::Failed) {
                audit()->log(
                    event: 'media.video_failed',
                    userId: $media->user_id,
                    auditable: $media,
                    newValues: [
                        'status' => $media->status?->value,
                        'processing_status' => $media->processing_status,
                        'upload_error' => $media->upload_error,
                    ],
                    metadata: [
                        'source' => 'media_video_sync',
                    ],
                );

                $this->notifyMediaVideoFailedAction->execute($media);
            }
        }

        return $this->loadMedia($media);
    }

    /**
     * @param  array<string,mixed>  $details
     * @return array{status: ?MediaStatus, processing_status: string, upload_error: ?string}
     */
    private function resolveStateFromPeerTubePayload(array $details): array
    {
        $stateLabel = strtolower((string) (data_get($details, 'state.label')
            ?? data_get($details, 'state.name')
            ?? data_get($details, 'state')
            ?? ''));

        $hasFiles = ! empty($details['files']) || ! empty($details['streamingPlaylists']);
        $stateId = (int) data_get($details, 'state.id', 0);
        $waitTranscoding = filter_var($details['waitTranscoding'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (
            $stateId === 1
            || $hasFiles
            || (! $waitTranscoding && (str_contains($stateLabel, 'ready') || str_contains($stateLabel, 'publish') || str_contains($stateLabel, 'pubblic')))
        ) {
            return [
                'status' => MediaStatus::Ready,
                'processing_status' => 'ready',
                'upload_error' => null,
            ];
        }

        if (
            str_contains($stateLabel, 'fail')
            || str_contains($stateLabel, 'error')
            || str_contains($stateLabel, 'reject')
            || str_contains($stateLabel, 'invalid')
            || str_contains($stateLabel, 'blacklist')
        ) {
            return [
                'status' => MediaStatus::Failed,
                'processing_status' => 'failed',
                'upload_error' => $stateLabel !== '' ? $stateLabel : __('media.validation.peertube_upload_failed'),
            ];
        }

        return [
            'status' => MediaStatus::Processing,
            'processing_status' => 'processing',
            'upload_error' => null,
        ];
    }

    private function loadMedia(MediaItem $media): MediaItem
    {
        return $media->fresh()?->loadCount(['likes', 'comments', 'snaps'])
            ->loadExists([
                'likes as is_liked' => fn ($query) => $query->where('user_id', 0),
            ])
            ->load('user.profile') ?? $media;
    }
}
