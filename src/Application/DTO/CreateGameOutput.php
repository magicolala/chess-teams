<?php

namespace App\Application\DTO;

final class CreateGameOutput
{
    public function __construct(
        public readonly string $gameId,
        public readonly string $inviteCode,
        public readonly int $turnDurationSec
    ) {
    }
}
