<?php

namespace Domain\Feedback\Actions;

use Domain\User\Models\User;
use Illuminate\Http\Client\PendingRequest;
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

        $endpoint = $baseUrl.'/api/v3/projects/'.rawurlencode($project).'/work_packages';
        $ip = $this->resolveIpv4($endpoint);
        $response = $this->postJson($endpoint, $token, $payload, $ip);

        if (! $response->successful()) {
            throw new RuntimeException($this->failureMessage($response));
        }

        $workPackageId = (int) $response->json('id');

        if ($workPackageId < 1) {
            throw new RuntimeException('OpenProject non ha restituito il task creato.');
        }

        if ($screenshot instanceof UploadedFile) {
            $this->attachScreenshot($baseUrl, $token, $workPackageId, $screenshot, $ip);
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

    private function attachScreenshot(string $baseUrl, string $token, int $workPackageId, UploadedFile $screenshot, ?string $ip): void
    {
        $filename = $screenshot->getClientOriginalName() ?: 'screenshot.png';

        $response = $this->postMultipart(
            $baseUrl.'/api/v3/work_packages/'.$workPackageId.'/attachments',
            $token,
            $screenshot->getRealPath(),
            $filename,
            $ip,
        );

        if (! $response->successful()) {
            throw new RuntimeException($this->failureMessage($response));
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postJson(string $url, string $token, array $payload, ?string $ip): Response
    {
        $response = $this->request($token, bearer: true, url: $url, ip: $ip)
            ->asJson()
            ->post($url, $payload);

        if ($response->status() !== 401) {
            return $response;
        }

        return $this->request($token, bearer: false, url: $url, ip: $ip)
            ->asJson()
            ->post($url, $payload);
    }

    private function postMultipart(string $url, string $token, string $path, string $filename, ?string $ip): Response
    {
        $fields = [
            'metadata' => json_encode(['fileName' => $filename]),
        ];

        $response = $this->request($token, bearer: true, url: $url, ip: $ip)
            ->attach('file', fopen($path, 'r'), $filename)
            ->post($url, $fields);

        if ($response->status() !== 401) {
            return $response;
        }

        return $this->request($token, bearer: false, url: $url, ip: $ip)
            ->attach('file', fopen($path, 'r'), $filename)
            ->post($url, $fields);
    }

    private function request(string $token, bool $bearer, string $url, ?string $ip): PendingRequest
    {
        $curl = [
            CURLOPT_NOSIGNAL => 1,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 8,
        ];

        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT) ?: 443;

        if (is_string($ip) && is_string($host) && $host !== '') {
            $curl[CURLOPT_RESOLVE] = ["{$host}:{$port}:{$ip}"];
        }

        $pending = Http::withHeaders([
            'Accept' => 'application/hal+json',
        ])->withOptions([
            'force_ip_resolve' => 'v4',
            'curl' => $curl,
        ])->connectTimeout(4)->timeout(8);

        return $bearer
            ? $pending->withToken($token)
            : $pending->withBasicAuth('apikey', $token);
    }

    private function resolveIpv4(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '' || str_ends_with($host, '.test')) {
            return null;
        }

        $response = Http::withOptions([
            'curl' => [
                CURLOPT_RESOLVE => ['cloudflare-dns.com:443:1.1.1.1'],
                CURLOPT_NOSIGNAL => 1,
            ],
        ])->withHeaders([
            'Accept' => 'application/dns-json',
        ])->connectTimeout(3)->timeout(5)->get('https://cloudflare-dns.com/dns-query', [
            'name' => $host,
            'type' => 'A',
        ]);

        $answers = $response->json('Answer');

        if (! is_array($answers)) {
            throw new RuntimeException('Impossibile raggiungere OpenProject.');
        }

        foreach ($answers as $answer) {
            $ip = $answer['data'] ?? null;

            if (($answer['type'] ?? null) === 1 && is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $ip;
            }
        }

        throw new RuntimeException('Impossibile raggiungere OpenProject.');
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
