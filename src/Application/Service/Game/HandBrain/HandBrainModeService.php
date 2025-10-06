<?php

namespace App\Application\Service\Game\HandBrain;

use App\Application\Service\Game\HandBrain\DTO\HandBrainState;
use App\Application\Service\Game\Traits\HandBrainTurnHelper;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class HandBrainModeService
{
    use HandBrainTurnHelper;

    public function __construct(
        private readonly GameRepositoryInterface $games,
        private readonly TeamRepositoryInterface $teams,
        private readonly TeamMemberRepositoryInterface $members,
        private readonly EntityManagerInterface $em,
    ) {
    }

    protected function getTeamMemberRepository(): TeamMemberRepositoryInterface
    {
        return $this->members;
    }

    public function enable(string $gameId, User $requestedBy): HandBrainState
    {
        $game = $this->games->get($gameId);
        if (!$game) {
            throw new NotFoundHttpException('game_not_found');
        }

        if (Game::STATUS_LIVE !== $game->getStatus()) {
            throw new ConflictHttpException('game_not_live');
        }

        if ('hand_brain' !== $game->getMode()) {
            throw new ConflictHttpException('hand_brain_mode_disabled');
        }

        $membership = $this->members->findOneByGameAndUser($game, $requestedBy);
        if (!$membership || !$membership->isActive()) {
            throw new AccessDeniedHttpException('hand_brain_not_participant');
        }

        $teamToPlayName = $game->getTurnTeam();
        $teamToPlay = $this->teams->findOneByGameAndName($game, $teamToPlayName);
        if (!$teamToPlay instanceof Team) {
            throw new NotFoundHttpException('team_not_found');
        }

        if ($membership->getTeam()->getId() !== $teamToPlay->getId()) {
            throw new AccessDeniedHttpException('hand_brain_not_team_turn');
        }

        $order = $this->members->findActiveOrderedByTeam($teamToPlay);
        if (0 === count($order)) {
            throw new ConflictHttpException('hand_brain_no_active_players');
        }

        $this->refreshHandBrainStateForTeam($game, $teamToPlay);
        $game->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return new HandBrainState(
            $game->getId(),
            $game->getHandBrainCurrentRole(),
            $game->getHandBrainPieceHint(),
            $game->getHandBrainBrainMemberId(),
            $game->getHandBrainHandMemberId(),
        );
    }
}
