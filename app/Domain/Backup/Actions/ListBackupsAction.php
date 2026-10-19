<?php

namespace Domain\Backup\Actions;

use Domain\Backup\Support\BackupRunState;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupDestination;
use Throwable;

class ListBackupsAction
{
    /**
     * @return array{
     *     configured: bool,
     *     reachable: bool,
     *     healthy: bool,
     *     disk: string,
     *     used_bytes: int,
     *     newest_at: ?string,
     *     error: ?string,
     *     backups: list<array{path: string, created_at: string, size_bytes: int}>,
     *     run: array{state: string, queued_at: ?string, finished_at: ?string, error: ?string}
     * }
     */
    public function execute(): array
    {
        $disk = (string) config('backup.backup.destination.disks.0', 'backups');
        $name = (string) config('backup.backup.name', 'slamin');
        $configured = filled(config('filesystems.disks.backups.key'))
            && filled(config('filesystems.disks.backups.secret'))
            && filled(config('filesystems.disks.backups.bucket'))
            && filled(config('filesystems.disks.backups.endpoint'));

        if (! $configured) {
            return $this->payload($disk, configured: false);
        }

        try {
            $destination = BackupDestination::create($disk, $name);
        } catch (Throwable $exception) {
            return $this->payload($disk, configured: true, error: $exception->getMessage());
        }

        if ($destination->disk() === null) {
            return $this->payload(
                $disk,
                configured: true,
                error: $destination->connectionError()?->getMessage(),
            );
        }

        try {
            $destination->disk()->files($name);
        } catch (Throwable $exception) {
            if (! $this->isEmptyPrefixError($exception)) {
                return $this->payload($disk, configured: true, error: $exception->getMessage());
            }
        }

        $collection = $destination->backups();

        return $this->payload(
            $disk,
            configured: true,
            reachable: true,
            usedBytes: (int) $collection->size(),
            newestAt: $collection->newest()?->date()->toIso8601String(),
            backups: $collection
                ->map(fn (Backup $backup): array => [
                    'path' => basename($backup->path()),
                    'created_at' => $backup->date()->toIso8601String(),
                    'size_bytes' => (int) $backup->sizeInBytes(),
                ])
                ->values()
                ->all(),
        );
    }

    /**
     * @param  list<array{path: string, created_at: string, size_bytes: int}>  $backups
     * @return array{
     *     configured: bool,
     *     reachable: bool,
     *     healthy: bool,
     *     disk: string,
     *     used_bytes: int,
     *     newest_at: ?string,
     *     error: ?string,
     *     backups: list<array{path: string, created_at: string, size_bytes: int}>,
     *     run: array{state: string, queued_at: ?string, finished_at: ?string, error: ?string}
     * }
     */
    private function payload(
        string $disk,
        bool $configured,
        bool $reachable = false,
        int $usedBytes = 0,
        ?string $newestAt = null,
        ?string $error = null,
        array $backups = [],
    ): array {
        return [
            'configured' => $configured,
            'reachable' => $reachable,
            'healthy' => $configured && $reachable,
            'disk' => $disk,
            'used_bytes' => $usedBytes,
            'newest_at' => $newestAt,
            'error' => $error,
            'backups' => $backups,
            'run' => BackupRunState::status(),
        ];
    }

    private function isEmptyPrefixError(Throwable $exception): bool
    {
        $haystack = $exception->getMessage();
        $previous = $exception->getPrevious();

        if ($previous instanceof Throwable) {
            $haystack .= ' '.$previous->getMessage();
        }

        return str_contains($haystack, 'NoSuchKey')
            || str_contains($haystack, 'specified key does not exist');
    }
}
