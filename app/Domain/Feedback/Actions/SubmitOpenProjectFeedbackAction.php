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

        if ($baseUrl === '' || $token === '' || $project === '') {
            throw new RuntimeException('openproject_not_configured');
        }

        $payload = [
            'subject' => $subject,
            'description' => [
                'format' => 'markdown',
                'raw' => $this->description($user, $description, $url),
            ],
        ];

        $endpoint = $baseUrl.'/api/v3/projects/'.rawurlencode($project).'/work_packages';

        if (! app()->runningUnitTests()) {
            return $this->executeCli($endpoint, $token, $payload, $screenshot);
        }

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

    /**
     * @param  array<string, mixed>  $payload
     */
    private function executeCli(string $endpoint, string $token, array $payload, ?UploadedFile $screenshot): int
    {
        $bodyFile = tempnam(sys_get_temp_dir(), 'opf');
        $headerFile = tempnam(sys_get_temp_dir(), 'opfh');

        if ($bodyFile === false || $headerFile === false) {
            throw new RuntimeException('Impossibile preparare l\'invio del feedback.');
        }

        try {
            [$formStatus, $formBody] = $this->postCliJson(
                $endpoint.'/form',
                $token,
                $payload,
                $bodyFile,
                $headerFile,
            );
            $form = json_decode($formBody, true);

            if ($formStatus < 200 || $formStatus >= 300 || ! is_array($form)) {
                throw new RuntimeException($this->cliFailure($formStatus, $form, $formBody));
            }

            $validationErrors = data_get($form, '_embedded.validationErrors', []);

            if (is_array($validationErrors) && $validationErrors !== []) {
                throw new RuntimeException($this->formValidationMessage($validationErrors));
            }

            $validatedPayload = data_get($form, '_embedded.payload');

            if (! is_array($validatedPayload)) {
                throw new RuntimeException('OpenProject non ha restituito un payload valido.');
            }

            [$status, $body] = $this->postCliJson(
                $endpoint,
                $token,
                $validatedPayload,
                $bodyFile,
                $headerFile,
            );
            $decoded = json_decode($body, true);
            $workPackageId = (int) (is_array($decoded) ? ($decoded['id'] ?? 0) : 0);

            if ($status < 200 || $status >= 300 || $workPackageId < 1) {
                throw new RuntimeException($this->cliFailure($status, $decoded, $body));
            }

            if ($screenshot instanceof UploadedFile) {
                $this->attachCli($endpoint, $token, $workPackageId, $screenshot, $headerFile);
            }

            return $workPackageId;
        } finally {
            @unlink($bodyFile);
            @unlink($headerFile);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: int, 1: string}
     */
    private function postCliJson(string $url, string $token, array $payload, string $bodyFile, string $headerFile): array
    {
        file_put_contents($bodyFile, json_encode($payload, JSON_THROW_ON_ERROR));
        $this->writeAuthHeader($headerFile, $token, bearer: true);
        $result = $this->runCurl([
            '-H', '@'.$headerFile,
            '-H', 'Content-Type: application/json',
            '-H', 'Accept: application/hal+json',
            '--data-binary', '@'.$bodyFile,
            $url,
        ], 8);

        if ($result[0] !== 401) {
            return $result;
        }

        $this->writeAuthHeader($headerFile, $token, bearer: false);

        return $this->runCurl([
            '-H', '@'.$headerFile,
            '-H', 'Content-Type: application/json',
            '-H', 'Accept: application/hal+json',
            '--data-binary', '@'.$bodyFile,
            $url,
        ], 8);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    private function formValidationMessage(array $errors): string
    {
        $messages = [];

        foreach ($errors as $field => $error) {
            $message = is_array($error) ? ($error['message'] ?? null) : null;

            if (is_string($message) && trim($message) !== '') {
                $messages[] = $field.': '.trim($message);
            }
        }

        $messages = array_values(array_unique($messages));

        return $messages !== []
            ? implode(' ', $messages)
            : 'Il feedback non rispetta i vincoli configurati in OpenProject.';
    }

    private function attachCli(string $endpoint, string $token, int $workPackageId, UploadedFile $screenshot, string $headerFile): void
    {
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($screenshot->getClientOriginalName() ?: '')) ?: 'screenshot.png';
        $metaFile = tempnam(sys_get_temp_dir(), 'opm');

        if ($metaFile === false) {
            throw new RuntimeException('Impossibile allegare lo screenshot.');
        }

        try {
            file_put_contents($metaFile, json_encode(['fileName' => $filename], JSON_THROW_ON_ERROR));
            $attachmentUrl = preg_replace('#/projects/[^/]+/work_packages$#', '/work_packages/'.$workPackageId.'/attachments', $endpoint);
            $this->writeAuthHeader($headerFile, $token, bearer: true);
            [$status, $body] = $this->runCurl([
                '-H', '@'.$headerFile,
                '-H', 'Accept: application/hal+json',
                '-F', 'metadata=<'.$metaFile.';type=application/json',
                '-F', 'file=@'.$screenshot->getRealPath().';filename='.$filename,
                (string) $attachmentUrl,
            ], 12);

            $decoded = json_decode($body, true);

            if ($status < 200 || $status >= 300) {
                throw new RuntimeException($this->cliFailure($status, $decoded, $body));
            }
        } finally {
            @unlink($metaFile);
        }
    }

    private function writeAuthHeader(string $headerFile, string $token, bool $bearer): void
    {
        $value = $bearer
            ? 'Bearer '.$token
            : 'Basic '.base64_encode('apikey:'.$token);

        file_put_contents($headerFile, 'Authorization: '.$value."\n");
        chmod($headerFile, 0600);
    }

    /**
     * @param  list<string>  $arguments
     * @return array{0: int, 1: string}
     */
    private function runCurl(array $arguments, int $timeoutSeconds): array
    {
        $binary = $this->curlBinary();
        $bodyFile = tempnam(sys_get_temp_dir(), 'opb');

        if ($bodyFile === false) {
            throw new RuntimeException('Impossibile preparare l\'invio del feedback.');
        }

        $command = array_merge([
            $binary,
            '-4',
            '-sS',
            '--max-time', (string) $timeoutSeconds,
            '--connect-timeout', '3',
            '-o', $bodyFile,
            '-w', '%{http_code}',
        ], $arguments);

        $pipes = [];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        if (! is_resource($process)) {
            @unlink($bodyFile);
            throw new RuntimeException('Impossibile contattare OpenProject.');
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stderr = '';
        $started = microtime(true);

        try {
            while (true) {
                $stderr .= (string) stream_get_contents($pipes[2]);
                $status = proc_get_status($process);

                if (! $status['running']) {
                    break;
                }

                if ((microtime(true) - $started) > ($timeoutSeconds + 2)) {
                    proc_terminate($process, 9);
                    throw new RuntimeException('OpenProject non ha risposto in tempo.');
                }

                usleep(50000);
            }

            $stderr .= (string) stream_get_contents($pipes[2]);
            $httpCode = (int) trim((string) stream_get_contents($pipes[1]));
            $body = (string) file_get_contents($bodyFile);

            if ($httpCode < 100) {
                Log::warning('OpenProject curl failed', ['error' => mb_substr($stderr, 0, 500)]);
                throw new RuntimeException('Impossibile raggiungere OpenProject.');
            }

            return [$httpCode, $body];
        } finally {
            foreach ($pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            proc_close($process);
            @unlink($bodyFile);
        }
    }

    private function curlBinary(): string
    {
        foreach (['/usr/bin/curl', '/bin/curl'] as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        throw new RuntimeException('curl non è disponibile sul server.');
    }

    /**
     * @param  array<string, mixed>|null  $decoded
     */
    private function cliFailure(int $status, ?array $decoded, string $body): string
    {
        $message = is_array($decoded) ? ($decoded['message'] ?? null) : null;
        $text = is_string($message) && trim($message) !== ''
            ? trim($message)
            : 'OpenProject ha rifiutato il feedback.';

        Log::warning('OpenProject feedback failed', [
            'status' => $status,
            'body' => mb_substr($body, 0, 500),
        ]);

        return $text.' (HTTP '.$status.')';
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
