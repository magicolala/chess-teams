<?php

namespace App\Application\DTO;

final class EnableFastModeOutput
{
    public function __construct(
        public string $gameId,
        public bool $fastModeEnabled,
        public int $fastModeDeadlineTs,
        public int $turnDeadlineTs,
    ) {
    }
}
