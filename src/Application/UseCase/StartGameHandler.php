<?php

namespace App\Application\UseCase;

use App\Application\DTO\StartGameInput;
use App\Application\DTO\StartGameOutput;
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

final class StartGameHandler
{
    public function __construct(
        private GameRepositoryInterface $games,
        private TeamRepositoryInterface $teams,
        private TeamMemberRepositoryInterface $members,
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(StartGameInput $in, User $requestedBy): StartGameOutput
    {
        $game = $this->games->get($in->gameId);
        if (!$game) {
            throw new NotFoundHttpException('game_not_found');
        }

        // autorisation simple : seul le créateur peut démarrer (tu pourras élargir après)
        if ($game->getCreatedBy()?->getId() !== $requestedBy->getId()) {
            throw new AccessDeniedHttpException('only_creator_can_start');
        }

        if (Game::STATUS_LOBBY !== $game->getStatus()) {
            throw new ConflictHttpException('already_started_or_finished');
        }

        $teamA = $this->teams->findOneByGameAndName($game, Team::NAME_A);
        $teamB = $this->teams->findOneByGameAndName($game, Team::NAME_B);
        if (!$teamA || !$teamB) {
            throw new NotFoundHttpException('teams_not_found');
        }

        $countA = $this->members->countActiveByTeam($teamA);
        $countB = $this->members->countActiveByTeam($teamB);
        if (0 === $countA || 0 === $countB) {
            throw new ConflictHttpException('each_team_must_have_at_least_one_member');
        }

        // Vérifier que tous les joueurs actifs sont prêts
        if (!$this->members->areAllActivePlayersReady($game)) {
            throw new ConflictHttpException('all_players_must_be_ready');
        }

        $now = new \DateTimeImmutable();
        // Par défaut, mode libre : 14 jours maximum
        $deadline = $now->modify('+14 days');

        $game
            ->setStatus(Game::STATUS_LIVE)
            ->setTurnTeam(Game::TEAM_A)
            ->setTurnDeadline($deadline)
            ->setFastModeEnabled(false)
            ->setFastModeDeadline(null)
            ->setUpdatedAt($now)
        ;

        $this->em->flush();

        return new StartGameOutput(
            gameId: $game->getId(),
            status: $game->getStatus(),
            turnTeam: $game->getTurnTeam(),
            turnDeadlineTs: $deadline->getTimestamp() * 1000
        );
    }
}
