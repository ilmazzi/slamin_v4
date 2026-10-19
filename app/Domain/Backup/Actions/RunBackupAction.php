<?php

namespace Domain\Backup\Actions;

use Domain\Backup\Support\BackupRunState;

class RunBackupAction
{
    public function execute(): void
    {
        BackupRunState::request();
    }
}
