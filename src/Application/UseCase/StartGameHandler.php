<?php

namespace App\Application\UseCase;

use App\Application\DTO\StartGameInput;
use App\Application\DTO\StartGameOutput;
use App\Application\Service\Game\GameLifecycleServiceInterface;
use App\Domain\Repository\GameRepositoryInterface;
use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class StartGameHandler
{
    public function __construct(
        private GameRepositoryInterface $games,
        private GameLifecycleServiceInterface $lifecycle,
    ) {
    }

    public function __invoke(StartGameInput $in, User $requestedBy): StartGameOutput
    {
        $game = $this->games->get($in->gameId);
        if (!$game) {
            throw new NotFoundHttpException('game_not_found');
        }

        $summary = $this->lifecycle->start($game, $requestedBy);

        return new StartGameOutput(
            gameId: $summary->gameId,
            status: $summary->status,
            turnTeam: $summary->turnTeam,
            turnDeadlineTs: $summary->turnDeadline->getTimestamp() * 1000
        );
    }
}
