<?php

namespace App\Application\DTO;

final class TimeoutDecisionInput
{
    public function __construct(
        public string $gameId,
        public string $userId,
        /** @var 'end'|'allow_next' */
        public string $decision,
    ) {
    }
}
