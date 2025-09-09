<?php

namespace App\Application\DTO;

final class TimeoutDecisionOutput
{
    public function __construct(
        public string $gameId,
        public string $status,
        public ?string $result,
        public bool $pending,
        public ?string $turnTeam,
        public ?int $turnDeadlineTs
    ) {
    }
}
