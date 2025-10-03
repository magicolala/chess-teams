<?php

namespace App\Application\Service\Game\DTO;

final class GameStartSummary
{
    public function __construct(
        public readonly string $gameId,
        public readonly string $status,
        public readonly string $turnTeam,
        public readonly \DateTimeImmutable $turnDeadline,
    ) {
    }
}
