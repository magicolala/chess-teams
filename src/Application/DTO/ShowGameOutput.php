<?php

namespace App\Application\DTO;

final class ShowGameOutput
{
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly string $fen,
        public readonly int $ply,
        public readonly string $turnTeam,
        public readonly ?int $turnDeadlineTs,
        public readonly array $teamA, // ['currentIndex'=>int, 'members'=>[...]]
        public readonly array $teamB
    ) {
    }
}
