<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class ShowRealtimeConfigController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $connection = config('broadcasting.connections.reverb', []);
        $key = trim((string) ($connection['key'] ?? ''));
        $host = $this->clientHost($connection);
        $scheme = trim((string) ($connection['options']['scheme'] ?? 'https')) ?: 'https';
        $port = (int) ($connection['options']['port'] ?? ($scheme === 'https' ? 443 : 80));
        $enabled = config('broadcasting.default') === 'reverb'
            && $key !== ''
            && $host !== '';

        return ApiResponse::success([
            'realtime' => [
                'enabled' => $enabled,
                'key' => $enabled ? $key : null,
                'host' => $enabled ? $host : null,
                'port' => $enabled ? $port : null,
                'scheme' => $enabled ? $scheme : null,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $connection
     */
    private function clientHost(array $connection): string
    {
        $host = trim((string) ($connection['options']['host'] ?? ''));
        $loopback = ['127.0.0.1', 'localhost', '0.0.0.0', '::1'];

        if ($host !== '' && ! in_array(strtolower($host), $loopback, true)) {
            return $host;
        }

        $fromApp = parse_url((string) config('app.url'), PHP_URL_HOST);

        return is_string($fromApp) && $fromApp !== '' ? $fromApp : $host;
    }
}
