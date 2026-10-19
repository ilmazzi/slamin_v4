<?php

namespace Domain\Backup\Support;

use Illuminate\Support\Facades\Cache;

class BackupRunState
{
    public const REQUEST_KEY = 'backup.manual_requested';

    public const STATUS_KEY = 'backup.manual_status';

    public static function request(): void
    {
        Cache::put(self::REQUEST_KEY, true, now()->addHours(2));
        Cache::put(self::STATUS_KEY, [
            'state' => 'queued',
            'queued_at' => now()->toIso8601String(),
            'finished_at' => null,
            'error' => null,
        ], now()->addDay());
    }

    public static function requested(): bool
    {
        return Cache::has(self::REQUEST_KEY);
    }

    public static function markRunning(): void
    {
        Cache::forget(self::REQUEST_KEY);
        $current = Cache::get(self::STATUS_KEY, []);

        Cache::put(self::STATUS_KEY, [
            'state' => 'running',
            'queued_at' => is_array($current) ? ($current['queued_at'] ?? now()->toIso8601String()) : now()->toIso8601String(),
            'finished_at' => null,
            'error' => null,
        ], now()->addDay());
    }

    public static function markFinished(?string $error): void
    {
        Cache::forget(self::REQUEST_KEY);
        $current = Cache::get(self::STATUS_KEY, []);

        Cache::put(self::STATUS_KEY, [
            'state' => $error ? 'failed' : 'finished',
            'queued_at' => is_array($current) ? ($current['queued_at'] ?? null) : null,
            'finished_at' => now()->toIso8601String(),
            'error' => $error,
        ], now()->addDay());
    }

    /**
     * @return array{state: string, queued_at: ?string, finished_at: ?string, error: ?string}
     */
    public static function status(): array
    {
        $status = Cache::get(self::STATUS_KEY);

        return [
            'state' => is_array($status) ? (string) ($status['state'] ?? 'idle') : 'idle',
            'queued_at' => is_array($status) ? ($status['queued_at'] ?? null) : null,
            'finished_at' => is_array($status) ? ($status['finished_at'] ?? null) : null,
            'error' => is_array($status) ? ($status['error'] ?? null) : null,
        ];
    }
}
