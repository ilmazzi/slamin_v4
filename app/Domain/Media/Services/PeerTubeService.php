<?php

namespace Domain\Media\Services;

use Domain\Media\Actions\ResolveMediaSettingsAction;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PeerTubeService
{
    private ?string $accessToken = null;

    public function __construct(
        private readonly ResolveMediaSettingsAction $resolveMediaSettingsAction,
    ) {}

    public function isConfigured(): bool
    {
        $settings = $this->resolveMediaSettingsAction->execute();

        return filled($settings['peertube_base_url'])
            && filled($settings['peertube_admin_username'])
            && filled($settings['peertube_admin_password'])
            && filled($settings['peertube_upload_channel_id']);
    }

    public function getBaseUrl(): string
    {
        $settings = $this->resolveMediaSettingsAction->execute();

        return rtrim((string) ($settings['peertube_base_url'] ?? ''), '/');
    }

    public function testConnection(): bool
    {
        try {
            return filled($this->getAdminToken());
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string,mixed>  $videoData
     * @return array<string,mixed>
     */
    public function uploadVideo(string $videoPath, array $videoData): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException(__('media.validation.peertube_not_configured'));
        }

        if (! is_file($videoPath)) {
            throw new RuntimeException(__('media.validation.video_file_missing'));
        }

        $settings = $this->resolveMediaSettingsAction->execute();
        $token = $this->getAdminToken();
        $uploadFilename = $this->uploadFilename(
            $videoPath,
            isset($videoData['filename']) ? (string) $videoData['filename'] : null,
        );
        $uploadMimeType = (string) ($videoData['mimeType'] ?? 'application/octet-stream');
        if (! preg_match('#^[a-z0-9.+-]+/[a-z0-9.+-]+$#i', $uploadMimeType)) {
            $uploadMimeType = 'application/octet-stream';
        }

        $request = $this->baseRequest((int) config('services.peertube.upload_timeout', 900))
            ->withToken($token)
            ->attach('videofile', fopen($videoPath, 'r'), $uploadFilename, [
                'Content-Type' => $uploadMimeType,
            ])
            ->asMultipart();

        $payload = [
            'channelId' => (string) $settings['peertube_upload_channel_id'],
            'name' => (string) ($videoData['name'] ?? 'Untitled video'),
            'privacy' => (string) ($videoData['privacy'] ?? $settings['peertube_default_privacy']),
            'category' => (string) ($videoData['category'] ?? $settings['peertube_default_category']),
            'licence' => (string) ($videoData['licence'] ?? $settings['peertube_default_licence']),
            'commentsPolicy' => (string) ($videoData['commentsPolicy'] ?? $settings['peertube_comments_policy']),
            'downloadEnabled' => ($videoData['downloadEnabled'] ?? $settings['peertube_download_enabled']) ? 'true' : 'false',
            'language' => (string) ($videoData['language'] ?? app()->getLocale()),
            'nsfw' => ($videoData['nsfw'] ?? false) ? 'true' : 'false',
        ];

        $description = trim((string) ($videoData['description'] ?? ''));
        if ($description !== '') {
            $payload['description'] = $description;
        }

        if (! empty($videoData['tags']) && is_array($videoData['tags'])) {
            foreach ($videoData['tags'] as $tag) {
                $request = $request->attach('tags[]', (string) $tag);
            }
        }

        $response = $request->post($this->getBaseUrl().'/api/v1/videos/upload', $payload);

        if (! $response->successful()) {
            throw new RuntimeException($response->body() ?: __('media.validation.peertube_upload_failed'));
        }

        $data = $response->json();

        return $data['video'] ?? $data;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function fetchVideoDetails(string $videoIdOrUuid): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $response = $this->baseRequest()->get($this->getBaseUrl().'/api/v1/videos/'.$videoIdOrUuid);

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }

    public function buildWatchUrl(?string $shortUuid, ?string $uuid): ?string
    {
        $identifier = $shortUuid ?: $uuid;

        return $identifier
            ? $this->getBaseUrl().'/videos/watch/'.$identifier
            : null;
    }

    public function buildEmbedUrl(?string $shortUuid, ?string $uuid): ?string
    {
        $identifier = $shortUuid ?: $uuid;

        return $identifier
            ? $this->getBaseUrl().'/videos/embed/'.$identifier
            : null;
    }

    public function resolveAssetUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim($this->getBaseUrl(), '/').'/'.ltrim($path, '/');
    }

    /**
     * @param  array<string, mixed>|null  $details
     */
    public function resolveThumbnailUrl(?array $details): ?string
    {
        if (! is_array($details)) {
            return null;
        }

        return $this->resolveAssetUrl(
            $details['previewPath'] ?? $details['thumbnailPath'] ?? null
        );
    }

    private function getAdminToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $settings = $this->resolveMediaSettingsAction->execute();
        $clientResponse = $this->baseRequest()->get($this->getBaseUrl().'/api/v1/oauth-clients/local');

        if (! $clientResponse->successful()) {
            throw new RuntimeException($clientResponse->body() ?: 'Unable to resolve PeerTube OAuth client.');
        }

        $client = $clientResponse->json();

        $tokenResponse = $this->baseRequest()
            ->asForm()
            ->post($this->getBaseUrl().'/api/v1/users/token', [
                'client_id' => $client['client_id'] ?? null,
                'client_secret' => $client['client_secret'] ?? null,
                'grant_type' => 'password',
                'username' => $settings['peertube_admin_username'],
                'password' => $settings['peertube_admin_password'],
            ]);

        if (! $tokenResponse->successful()) {
            throw new RuntimeException($tokenResponse->body() ?: 'Unable to authenticate against PeerTube.');
        }

        $this->accessToken = $tokenResponse->json('access_token');

        return $this->accessToken;
    }

    private function baseRequest(int $timeout = 60): PendingRequest
    {
        $settings = $this->resolveMediaSettingsAction->execute();

        $request = Http::connectTimeout(
            max(1, (int) config('services.peertube.connect_timeout', 15))
        )->timeout(max(1, $timeout));

        if (($settings['peertube_verify_ssl'] ?? true) === false) {
            $request = $request->withoutVerifying();
        }

        return $request;
    }

    private function uploadFilename(string $videoPath, ?string $originalName): string
    {
        $extension = strtolower(pathinfo(
            str_replace('\\', '/', (string) $originalName),
            PATHINFO_EXTENSION,
        ));

        if (! preg_match('/^[a-z0-9]{1,10}$/', $extension)) {
            $extension = strtolower(pathinfo($videoPath, PATHINFO_EXTENSION));
        }

        return preg_match('/^[a-z0-9]{1,10}$/', $extension)
            ? 'video.'.$extension
            : 'video';
    }
}
