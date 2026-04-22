<?php

namespace Domain\Media\Actions;

use Domain\Media\Models\MediaSnap;

class DeleteMediaSnapAction
{
    public function execute(MediaSnap $snap): void
    {
        $snap->delete();
    }
}
