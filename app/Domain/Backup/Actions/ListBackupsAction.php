<?php

namespace Domain\Backup\Actions;

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
     *     backups: list<array{path: string, created_at: string, size_bytes: int}>
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
            return [
                'configured' => false,
                'reachable' => false,
                'healthy' => false,
                'disk' => $disk,
                'used_bytes' => 0,
                'newest_at' => null,
                'error' => null,
                'backups' => [],
            ];
        }

        try {
            $destination = BackupDestination::create($disk, $name);
            $reachable = $destination->isReachable();
        } catch (Throwable $exception) {
            return [
                'configured' => true,
                'reachable' => false,
                'healthy' => false,
                'disk' => $disk,
                'used_bytes' => 0,
                'newest_at' => null,
                'error' => $exception->getMessage(),
                'backups' => [],
            ];
        }

        $error = $destination->connectionError()?->getMessage();
        $backups = [];
        $usedBytes = 0;
        $newestAt = null;

        if ($reachable) {
            $collection = $destination->backups();
            $usedBytes = (int) $collection->size();
            $newestAt = $collection->newest()?->date()->toIso8601String();
            $backups = $collection
                ->map(fn (Backup $backup): array => [
                    'path' => basename($backup->path()),
                    'created_at' => $backup->date()->toIso8601String(),
                    'size_bytes' => (int) $backup->sizeInBytes(),
                ])
                ->values()
                ->all();
        }

        return [
            'configured' => $configured,
            'reachable' => $reachable,
            'healthy' => $reachable && $configured,
            'disk' => $disk,
            'used_bytes' => $usedBytes,
            'newest_at' => $newestAt,
            'error' => $error,
            'backups' => $backups,
        ];
    }
}
