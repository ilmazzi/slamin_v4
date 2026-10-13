<?php

namespace Domain\Feedback\Actions;

use Domain\User\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SubmitOpenProjectFeedbackAction
{
    public function execute(User $user, string $subject, string $description, ?string $url, ?UploadedFile $screenshot): int
    {
        $baseUrl = rtrim((string) config('services.openproject.base_url'), '/');
        $token = (string) config('services.openproject.api_token');
        $project = trim((string) config('services.openproject.project'));
        $typeId = (int) config('services.openproject.type_id');

        if ($baseUrl === '' || $token === '' || $project === '' || $typeId < 1) {
            throw new RuntimeException('openproject_not_configured');
        }

        $payload = [
            'subject' => $subject,
            'description' => [
                'format' => 'markdown',
                'raw' => $this->description($user, $description, $url),
            ],
            '_links' => [
                'type' => [
                    'href' => '/api/v3/types/'.$typeId,
                ],
            ],
        ];

        $response = $this->postJson(
            $baseUrl.'/api/v3/projects/'.rawurlencode($project).'/work_packages',
            $token,
            $payload,
        );

        if (! $response->successful()) {
            throw new RuntimeException($this->failureMessage($response));
        }

        $workPackageId = (int) $response->json('id');

        if ($workPackageId < 1) {
            throw new RuntimeException('OpenProject non ha restituito il task creato.');
        }

        if ($screenshot instanceof UploadedFile) {
            $this->attachScreenshot($baseUrl, $token, $workPackageId, $screenshot);
        }

        return $workPackageId;
    }

    private function description(User $user, string $description, ?string $url): string
    {
        $lines = [trim($description), ''];

        if (filled($url)) {
            $lines[] = '**Pagina:** '.$url;
        }

        $name = $user->profile?->display_name ?: $user->profile?->nickname ?: $user->email;
        $lines[] = '**Utente:** '.$name.' ('.$user->email.')';

        return implode("\n", $lines);
    }

    private function attachScreenshot(string $baseUrl, string $token, int $workPackageId, UploadedFile $screenshot): void
    {
        $filename = $screenshot->getClientOriginalName() ?: 'screenshot.png';

        $response = $this->postMultipart(
            $baseUrl.'/api/v3/work_packages/'.$workPackageId.'/attachments',
            $token,
            $screenshot->getRealPath(),
            $filename,
        );

        if (! $response->successful()) {
            throw new RuntimeException($this->failureMessage($response));
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postJson(string $url, string $token, array $payload): Response
    {
        $response = $this->request($token, bearer: true)
            ->asJson()
            ->post($url, $payload);

        if ($response->status() !== 401) {
            return $response;
        }

        return $this->request($token, bearer: false)
            ->asJson()
            ->post($url, $payload);
    }

    private function postMultipart(string $url, string $token, string $path, string $filename): Response
    {
        $fields = [
            'metadata' => json_encode(['fileName' => $filename]),
        ];

        $response = $this->request($token, bearer: true)
            ->attach('file', fopen($path, 'r'), $filename)
            ->post($url, $fields);

        if ($response->status() !== 401) {
            return $response;
        }

        return $this->request($token, bearer: false)
            ->attach('file', fopen($path, 'r'), $filename)
            ->post($url, $fields);
    }

    private function request(string $token, bool $bearer): \Illuminate\Http\Client\PendingRequest
    {
        $pending = Http::withHeaders([
            'Accept' => 'application/hal+json',
        ])->timeout(20);

        return $bearer
            ? $pending->withToken($token)
            : $pending->withBasicAuth('apikey', $token);
    }

    private function failureMessage(Response $response): string
    {
        $message = $response->json('message');
        $text = is_string($message) && trim($message) !== ''
            ? trim($message)
            : 'OpenProject ha rifiutato il feedback.';

        Log::warning('OpenProject feedback failed', [
            'status' => $response->status(),
            'body' => mb_substr($response->body(), 0, 500),
        ]);

        return $text.' (HTTP '.$response->status().')';
    }
}
