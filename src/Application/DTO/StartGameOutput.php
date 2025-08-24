<?php

namespace App\Application\DTO;

final class StartGameOutput
{
    public function __construct(
        public readonly string $gameId,
        public readonly string $status,
        public readonly string $turnTeam,
        public readonly int $turnDeadlineTs // milliseconds epoch
    ) {
    }
}
