<?php

namespace App\Application\UseCase;

use App\Application\DTO\MakeMoveInput;
use App\Application\DTO\MakeMoveOutput;
use App\Application\Service\Game\GameMoveServiceInterface;
use App\Entity\User;

final class MakeMoveHandler
{
    public function __construct(
        private GameMoveServiceInterface $gameMoves,
    ) {
    }

    public function __invoke(MakeMoveInput $in, User $byUser): MakeMoveOutput
    {
        $result = $this->gameMoves->play($in, $byUser);
        $game = $result->game;

        return new MakeMoveOutput(
            $game->getId(),
            $result->ply,
            $game->getTurnTeam(),
            $game->getTurnDeadline()?->getTimestamp() * 1000,
            $game->getFen(),
            $game->getHandBrainCurrentRole(),
            $game->getHandBrainPieceHint(),
            $game->getHandBrainBrainMemberId(),
            $game->getHandBrainHandMemberId(),
        );
    }
}
