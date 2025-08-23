<?php

namespace App\Application\UseCase;

use App\Application\DTO\{JoinByCodeInput, JoinByCodeOutput};
use App\Domain\Repository\{
	InviteRepositoryInterface,
	TeamRepositoryInterface,
	TeamMemberRepositoryInterface
};
use App\Entity\{Game, Team, TeamMember, User};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\{NotFoundHttpException, ConflictHttpException, AccessDeniedHttpException};

final class JoinByCodeHandler
{
	public function __construct(
		private InviteRepositoryInterface $invites,
		private TeamRepositoryInterface $teams,
		private TeamMemberRepositoryInterface $members,
		private EntityManagerInterface $em
	) {}

	public function __invoke(JoinByCodeInput $in, User $user): JoinByCodeOutput
	{
		$invite = $this->invites->findOneByCode($in->inviteCode);
		if (!$invite) {
			throw new NotFoundHttpException('invalid_code');
		}

		$game = $invite->getGame();
		if ($game->getStatus() !== Game::STATUS_LOBBY) {
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
