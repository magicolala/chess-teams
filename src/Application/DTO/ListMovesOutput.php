<?php

namespace App\Application\DTO;

final class ListMovesOutput
{
    /** @param array<int, array<string, mixed>> $moves */
    public function __construct(
        public readonly string $gameId,
        public readonly array $moves, // [{ply, team, byUserId, uci, san, type, fenAfter, createdAt}]
    ) {
    }
}
