<?php

namespace App\Application\UseCase;

use App\Application\DTO\JoinByCodeInput;
use App\Application\DTO\JoinByCodeOutput;
use App\Domain\Repository\InviteRepositoryInterface;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class JoinByCodeHandler
{
    public function __construct(
        private InviteRepositoryInterface $invites,
        private TeamRepositoryInterface $teams,
        private TeamMemberRepositoryInterface $members,
        private EntityManagerInterface $em
    ) {
    }

    public function __invoke(JoinByCodeInput $in, User $user): JoinByCodeOutput
    {
        $invite = $this->invites->findOneByCode($in->inviteCode);
        if (!$invite) {
            throw new NotFoundHttpException('invalid_code');
        }

        $game = $invite->getGame();
        if (Game::STATUS_LOBBY !== $game->getStatus()) {
            throw new ConflictHttpException('already_started');
        }

        $teamA = $this->teams->findOneByGameAndName($game, Team::NAME_A);
        $teamB = $this->teams->findOneByGameAndName($game, Team::NAME_B);
        if (!$teamA || !$teamB) {
            throw new NotFoundHttpException('teams_not_found');
        }

        $countA = $this->members->countActiveByTeam($teamA);
        $countB = $this->members->countActiveByTeam($teamB);

        $team = $countA <= $countB ? $teamA : $teamB;

        // déjà membre ?
        $existing = $this->members->findOneByTeamAndUser($team, $user);
        if ($existing) {
            return new JoinByCodeOutput($team->getName(), $existing->getPosition());
        }

        $maxPos = $this->members->maxPositionByTeam($team);
        $position = $maxPos + 1;

        $member = new TeamMember($team, $user, $position);
        $this->members->add($member);
        $this->em->flush();

        return new JoinByCodeOutput($team->getName(), $position);
    }
}
