<?php

namespace App\Application\DTO;

final class JoinByCodeOutput
{
    public function __construct(
        public readonly string $teamName,
        public readonly int $position,
    ) {
    }
}
