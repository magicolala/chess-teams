<?php

namespace App\Application\UseCase;

use App\Application\DTO\TimeoutTickInput;
use App\Application\DTO\TimeoutTickOutput;
use App\Application\Service\Game\GameTimeoutService;
use App\Entity\User;

final class TimeoutTickHandler
{
    public function __construct(
        private GameTimeoutService $timeouts,
    ) {
    }

    public function __invoke(TimeoutTickInput $in, User $requestedBy): TimeoutTickOutput
    {
        unset($requestedBy);

        $result = $this->timeouts->handle($in);
        $game = $result->game;

        return new TimeoutTickOutput(
            $game->getId(),
            $result->timeoutTriggered,
            $result->ply,
            $game->getTurnTeam(),
            $game->getTurnDeadline()?->getTimestamp() * 1000,
            $game->getFen()
        );
    }
}
