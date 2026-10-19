<?php

namespace Domain\Backup\Actions;

use Domain\Backup\Jobs\RunSiteBackupJob;

class RunBackupAction
{
    public function execute(): void
    {
        RunSiteBackupJob::dispatch();
    }
}
