<?php

namespace App\Application\Service\Game\HandBrain\DTO;

final class HandBrainState
{
    public function __construct(
        public readonly string $gameId,
        public readonly ?string $currentRole,
        public readonly ?string $pieceHint,
        public readonly ?string $brainMemberId,
        public readonly ?string $handMemberId,
    ) {
    }
}
