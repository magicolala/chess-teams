<?php

namespace App\Application\DTO;

final class ClaimVictoryInput
{
    public function __construct(
        public string $gameId,
        public string $userId,
    ) {
    }
}
