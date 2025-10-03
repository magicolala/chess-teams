<?php

namespace App\Application\Service\Game;

use App\Application\DTO\MakeMoveInput;
use App\Application\Service\Game\DTO\MoveResult;
use App\Entity\User;

interface GameMoveServiceInterface
{
    public function play(MakeMoveInput $input, User $player): MoveResult;
}
