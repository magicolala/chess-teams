<?php

namespace App\Application\DTO;

final class ClaimVictoryOutput
{
    public function __construct(
        public string $gameId,
        public bool $claimed,
        public string $result,
        public string $status,
        public string $winnerTeam,
    ) {
    }
}
