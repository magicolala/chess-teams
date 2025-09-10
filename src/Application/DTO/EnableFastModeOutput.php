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
        // Backward/forward-compat: tests may read `$enabled` instead of `$fastModeEnabled`.
        $this->enabled = $this->fastModeEnabled;
    }

    /** @var bool mirrors $fastModeEnabled */
    public bool $enabled;
}
