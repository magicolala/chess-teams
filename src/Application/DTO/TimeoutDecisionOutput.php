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
        // Backward/forward-compat: expose both property names used across tests
        // Some tests use `$pending`, others expect `$decisionPending`.
        $this->decisionPending = $this->pending;
    }

    /** @var bool mirrors $pending */
    public bool $decisionPending;
}
