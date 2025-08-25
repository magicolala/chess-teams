<?php

namespace App\Application\DTO;

final class TimeoutTickInput
{
    public function __construct(
        public readonly string $gameId,
        public readonly string $requestedByUserId,
    ) {
    }
}

