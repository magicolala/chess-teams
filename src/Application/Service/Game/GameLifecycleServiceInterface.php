<?php

namespace App\Application\Service\Game;

use App\Application\Service\Game\DTO\GameStartSummary;
use App\Entity\Game;
use App\Entity\User;

interface GameLifecycleServiceInterface
{
    public function start(Game $game, User $requestedBy): GameStartSummary;
}
