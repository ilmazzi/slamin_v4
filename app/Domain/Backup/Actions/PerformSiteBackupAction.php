<?php

namespace Domain\Backup\Actions;

use Domain\Backup\Support\BackupRunState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Throwable;

class PerformSiteBackupAction
{
    public function execute(bool $force = false): void
    {
        if (! $force && ! BackupRunState::requested()) {
            return;
        }

        $lock = Cache::lock('site-backup-run', 3600);

        if (! $lock->get()) {
            return;
        }

        try {
            if (! $force && ! BackupRunState::requested()) {
                return;
            }

            BackupRunState::markRunning();

            $exit = Artisan::call('backup:run');
            $output = trim(Artisan::output());

            if ($exit !== 0) {
                BackupRunState::markFinished($output !== '' ? $output : "backup:run exited {$exit}");

                return;
            }

            BackupRunState::markFinished(null);
        } catch (Throwable $exception) {
            BackupRunState::markFinished($exception->getMessage());

            throw $exception;
        } finally {
            $lock->release();
        }
    }
}
