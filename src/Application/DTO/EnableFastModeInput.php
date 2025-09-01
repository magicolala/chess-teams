<?php

namespace App\Application\DTO;

final class EnableFastModeInput
{
    public function __construct(
        public string $gameId,
        public string $userId,
    ) {
    }
}
