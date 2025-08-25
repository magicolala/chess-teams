<?php

namespace App\Application\DTO;

final class MakeMoveInput
{
    public function __construct(
        public readonly string $gameId,
        public readonly string $uci,
        public readonly string $userId,
    ) {
    }
}
