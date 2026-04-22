<?php

namespace Domain\Media\Actions;

use Domain\Media\Services\PeerTubeService;

class TestPeerTubeConnectionAction
{
    public function __construct(
        private readonly PeerTubeService $peerTubeService,
    ) {
    }

    public function execute(): bool
    {
        return $this->peerTubeService->testConnection();
    }
}
