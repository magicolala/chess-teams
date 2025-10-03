<?php

namespace App\Application\Service\Game;

use App\Application\Service\Game\DTO\GameStartSummary;
use App\Application\Service\Werewolf\WerewolfRoleAssigner;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GameLifecycleService
{
    public function __construct(
        private readonly TeamRepositoryInterface $teams,
        private readonly TeamMemberRepositoryInterface $members,
        private readonly EntityManagerInterface $em,
        private readonly WerewolfRoleAssigner $werewolfAssigner,
    ) {
    }

    public function start(Game $game, User $requestedBy): GameStartSummary
    {
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

        if (!$this->members->areAllActivePlayersReady($game)) {
            throw new ConflictHttpException('all_players_must_be_ready');
        }

        $now = new \DateTimeImmutable();
        $deadline = $now->modify('+14 days');

        $game
            ->setStatus(Game::STATUS_LIVE)
            ->setTurnTeam(Game::TEAM_A)
            ->setTurnDeadline($deadline)
            ->setFastModeEnabled(false)
            ->setFastModeDeadline(null)
            ->setUpdatedAt($now);

        if ('werewolf' === $game->getMode()) {
            $this->assignWerewolfRoles($game, $teamA, $teamB);
        }

        $this->em->flush();

        return new GameStartSummary(
            $game->getId(),
            $game->getStatus(),
            $game->getTurnTeam(),
            $deadline,
        );
    }

    private function assignWerewolfRoles(Game $game, Team $teamA, Team $teamB): void
    {
        $activeA = $this->members->findActiveOrderedByTeam($teamA);
        $activeB = $this->members->findActiveOrderedByTeam($teamB);

        $usersA = array_map(static fn ($m) => $m->getUser(), $activeA);
        $usersB = array_map(static fn ($m) => $m->getUser(), $activeB);

        if (count($usersA) + count($usersB) < 4) {
            return;
        }

        $this->werewolfAssigner->assignForGame($game, $usersA, $usersB);
    }
}
