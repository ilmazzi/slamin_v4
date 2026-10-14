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
        $host = trim((string) ($connection['options']['host'] ?? ''));
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
}
