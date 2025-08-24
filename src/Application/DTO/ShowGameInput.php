<?php

namespace App\Application\DTO;

final class ShowGameInput
{
    public function __construct(
        public readonly string $gameId
    ) {
    }
}
