<?php

namespace App\Application\UseCase;

use App\Application\DTO\TimeoutDecisionInput;
use App\Application\DTO\TimeoutDecisionOutput;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Lock\LockFactory;

final class DecideTimeoutHandler
{
    public function __construct(
        private GameRepositoryInterface $games,
        private TeamRepositoryInterface $teams,
        private TeamMemberRepositoryInterface $members,
        #[Autowire(service: 'lock.factory')]
        private LockFactory $lockFactory,
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(TimeoutDecisionInput $in, User $requestedBy): TimeoutDecisionOutput
    {
        $game = $this->games->get($in->gameId);
        if (!$game) {
            throw new NotFoundHttpException('game_not_found');
        }
        if (Game::STATUS_LIVE !== $game->getStatus()) {
            throw new ConflictHttpException('game_not_live');
        }
        if (!$game->isTimeoutDecisionPending()) {
            throw new ConflictHttpException('no_timeout_decision_pending');
        }
        if (!\in_array($in->decision, ['end', 'allow_next'], true)) {
            throw new BadRequestHttpException('invalid_decision');
        }

        $lock = $this->lockFactory->createLock('game:'.$game->getId(), 5.0);
        if (!$lock->acquire()) {
            throw new ConflictHttpException('locked');
        }

        try {
            $teamA = $this->teams->findOneByGameAndName($game, Team::NAME_A);
            $teamB = $this->teams->findOneByGameAndName($game, Team::NAME_B);
            if (!$teamA || !$teamB) {
                throw new NotFoundHttpException('teams_not_found');
            }

            $decisionTeam = $game->getTimeoutDecisionTeam(); // 'A' or 'B'
            $userMembership = $this->members->findOneByGameAndUser($game, $requestedBy);
            if (!$userMembership) {
                throw new AccessDeniedHttpException('player_not_in_game');
            }
            $userTeamName = Team::NAME_A === $userMembership->getTeam()->getName() ? Game::TEAM_A : Game::TEAM_B;
            if ($userTeamName !== $decisionTeam) {
                throw new AccessDeniedHttpException('not_your_team_to_decide');
            }

            $now = new \DateTimeImmutable();
            $timedOutTeam = $game->getTimeoutTimedOutTeam(); // 'A' or 'B'
            $teamTimedOut = $timedOutTeam === Game::TEAM_A ? $teamA : $teamB;

            if ('end' === $in->decision) {
                // Opponent ends the game: opponent wins by timeout
                $winnerTeam = $decisionTeam; // opponent team
                $result = $winnerTeam.'+'.$timedOutTeam.'timeout';
                $game->setResult($result);
                $game->setStatus(Game::STATUS_FINISHED);
                $game->setTurnDeadline(null);
                $game->resetTimeoutDecision();
                $game->setUpdatedAt($now);
                $this->em->flush();

                return new TimeoutDecisionOutput(
                    $game->getId(),
                    $game->getStatus(),
                    $game->getResult(),
                    false,
                    null,
                    null,
                );
            }

            // allow_next: allow next teammate of the timed-out team to play now
            $order = $this->members->findActiveOrderedByTeam($teamTimedOut) ?: [];
            $n = \count($order);
            if ($n > 0) {
                $teamTimedOut->setCurrentIndex(($teamTimedOut->getCurrentIndex() + 1) % $n);
            }

            // Keep it the timed-out team's turn, restore deadlines (free mode 14 days)
            $game->setTurnTeam($teamTimedOut->getName());
            $game->setFastModeEnabled(false);
            $game->setFastModeDeadline(null);
            $game->setTurnDeadline($now->modify('+14 days'));
            $game->resetTimeoutDecision();
            $game->setUpdatedAt($now);

            $this->em->flush();

            return new TimeoutDecisionOutput(
                $game->getId(),
                $game->getStatus(),
                $game->getResult(),
                false,
                $game->getTurnTeam(),
                $game->getTurnDeadline()?->getTimestamp() * 1000 ?? null,
            );
        } finally {
            $lock->release();
        }
    }
}
