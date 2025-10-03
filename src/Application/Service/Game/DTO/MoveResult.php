<?php

namespace App\Application\Service\Game\DTO;

use App\Entity\Game;

final class MoveResult
{
    public function __construct(
        public readonly Game $game,
        public readonly int $ply,
        public readonly \DateTimeImmutable $processedAt,
    ) {
    }
}
