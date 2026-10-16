<?php

namespace Domain\Media\Actions;

use Domain\Media\DTOs\StoreVideoMediaData;
use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaType;
use Domain\Media\Models\MediaItem;
use Domain\Media\Services\PeerTubeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StoreVideoMediaAction
{
    public function __construct(
        private readonly ResolveMediaSettingsAction $resolveMediaSettingsAction,
        private readonly ResolveUserMediaLimitsAction $resolveUserMediaLimitsAction,
        private readonly PeerTubeService $peerTubeService,
    ) {}

    public function execute(StoreVideoMediaData $data): MediaItem
    {
        $settings = $this->resolveMediaSettingsAction->execute();
        $limits = $this->resolveUserMediaLimitsAction->execute($data->userId);

        if ($limits['remaining_video_uploads'] <= 0) {
            throw ValidationException::withMessages([
                'file' => [__('media.validation.video_limit_reached')],
            ]);
        }

        if (! $this->peerTubeService->isConfigured()) {
            throw ValidationException::withMessages([
                'file' => [__('media.validation.peertube_not_configured')],
            ]);
        }

        if (($data->file->getSize() / 1024) > (int) $settings['video_max_size_kb']) {
            throw ValidationException::withMessages([
                'file' => [__('media.validation.video_too_large')],
            ]);
        }

        if (! in_array($data->file->getMimeType(), (array) $settings['video_allowed_mimes'], true)) {
            throw ValidationException::withMessages([
                'file' => [__('media.validation.video_mime_not_allowed')],
            ]);
        }

        $tempPath = $data->file->store('temp-videos', 'local');
        $fullPath = Storage::disk('local')->path($tempPath);

        try {
            $video = $this->peerTubeService->uploadVideo($fullPath, [
                'filename' => $data->file->getClientOriginalName(),
                'name' => $data->title,
                'description' => $data->description,
                'privacy' => $settings['peertube_default_privacy'],
                'category' => $settings['peertube_default_category'],
                'licence' => $settings['peertube_default_licence'],
                'commentsPolicy' => $settings['peertube_comments_policy'],
                'downloadEnabled' => $settings['peertube_download_enabled'],
                'language' => app()->getLocale(),
                'tags' => array_slice($data->tags, 0, 5),
            ]);

            $details = isset($video['id']) ? $this->peerTubeService->fetchVideoDetails((string) $video['id']) : null;

            return DB::transaction(function () use ($data, $video, $details) {
                $media = MediaItem::query()->create([
                    'user_id' => $data->userId,
                    'type' => MediaType::Video,
                    'title' => $data->title,
                    'description' => $data->description,
                    'visibility' => $data->visibility,
                    'status' => MediaStatus::Processing,
                    'mime_type' => $data->file->getMimeType(),
                    'file_size' => $data->file->getSize() ?? 0,
                    'uploaded_at' => now(),
                    'peertube_video_id' => (string) ($video['id'] ?? ''),
                    'peertube_uuid' => $video['uuid'] ?? null,
                    'peertube_short_uuid' => $video['shortUUID'] ?? null,
                    'peertube_url' => $this->peerTubeService->buildWatchUrl($video['shortUUID'] ?? null, $video['uuid'] ?? null),
                    'peertube_embed_url' => $this->peerTubeService->buildEmbedUrl($video['shortUUID'] ?? null, $video['uuid'] ?? null),
                    'peertube_thumbnail_url' => $this->peerTubeService->resolveThumbnailUrl($details),
                    'duration_seconds' => is_array($details) ? ($details['duration'] ?? null) : null,
                    'processing_status' => 'processing',
                ]);

                audit()->log(
                    event: 'media.video_uploaded',
                    userId: $data->userId,
                    auditable: $media,
                    newValues: [
                        'media_id' => $media->id,
                        'type' => $media->type?->value,
                        'peertube_video_id' => $media->peertube_video_id,
                    ],
                    metadata: [
                        'source' => 'media_video_store',
                    ],
                );

                return $media->load('user.profile');
            });
        } catch (\Throwable $exception) {
            Log::error('PeerTube video upload failed.', [
                'exception' => $exception,
                'user_id' => $data->userId,
                'file_size' => $data->file->getSize(),
                'mime_type' => $data->file->getMimeType(),
                'peertube_base_url' => $this->peerTubeService->getBaseUrl(),
            ]);

            throw ValidationException::withMessages([
                'file' => [__('media.validation.peertube_upload_failed')],
            ]);
        } finally {
            Storage::disk('local')->delete($tempPath);
        }
    }
}
