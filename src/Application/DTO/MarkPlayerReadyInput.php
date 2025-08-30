<?php

namespace App\Application\DTO;

final class MarkPlayerReadyInput
{
    public function __construct(
        public string $gameId,
        public string $userId,
        public bool $ready = true,
    ) {
    }
}
