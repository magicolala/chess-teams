<?php

namespace App\Application\DTO;

final class CreateGameInput
{
    public function __construct(
        public readonly string $creatorUserId,
        public readonly int $turnDurationSec = 60,
        public readonly string $visibility = 'private',
        public readonly string $mode = 'classic', // 'classic' | 'werewolf'
        public readonly bool $twoWolvesPerTeams = false,
    ) {
    }
}
