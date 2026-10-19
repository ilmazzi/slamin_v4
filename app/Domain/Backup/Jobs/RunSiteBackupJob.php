<?php

namespace Domain\Backup\Jobs;

use Domain\Backup\Actions\PerformSiteBackupAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunSiteBackupJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 3600;

    public function handle(PerformSiteBackupAction $action): void
    {
        $action->execute(force: true);
    }
}
