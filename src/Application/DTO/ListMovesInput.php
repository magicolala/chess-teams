<?php

namespace App\Application\DTO;

final class ListMovesInput
{
    public function __construct(
        public readonly string $gameId,
        public readonly ?int $sincePly = null,
    ) {
    }
}
