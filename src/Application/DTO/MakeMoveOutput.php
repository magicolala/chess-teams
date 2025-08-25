<?php

namespace App\Application\DTO;

final class MakeMoveOutput
{
    public function __construct(
        public readonly string $gameId,
        public readonly int $ply,
        public readonly string $turnTeam,
        public readonly int $turnDeadlineTs,
        public readonly string $fen,
    ) {
    }
}
