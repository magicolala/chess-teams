<?php

namespace App\Application\DTO;

final class MarkPlayerReadyOutput
{
    public function __construct(
        public string $gameId,
        public string $userId,
        public bool $ready,
        public bool $allPlayersReady,
        public int $readyPlayersCount,
        public int $totalPlayersCount,
    ) {
    }
}
