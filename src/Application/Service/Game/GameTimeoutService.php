<?php

namespace App\Application\Service\Game;

use App\Application\DTO\TimeoutTickInput;
use App\Application\Service\Game\DTO\TimeoutResult;
use App\Application\Service\GameEndEvaluator;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\MoveRepositoryInterface;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\Move;
use App\Entity\Team;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Lock\LockFactory;

final class GameTimeoutService
{
    public function __construct(
        private readonly GameRepositoryInterface $games,
        private readonly TeamRepositoryInterface $teams,
        private readonly TeamMemberRepositoryInterface $members,
        private readonly MoveRepositoryInterface $moves,
        private readonly LockFactory $lockFactory,
        private readonly EntityManagerInterface $em,
        private readonly GameEndEvaluator $endEvaluator,
    ) {
    }

    public function handle(TimeoutTickInput $input): TimeoutResult
    {
        $game = $this->fetchLiveGame($input->gameId);

        $lock = $this->lockFactory->createLock('game:'.$game->getId(), 5.0);
        if (!$lock->acquire()) {
            throw new ConflictHttpException('locked');
        }

        try {
            $now = new \DateTimeImmutable();
            $deadline = $game->getEffectiveDeadline();

            if (!$deadline || $now <= $deadline) {
                return new TimeoutResult($game, $game->getPly(), false, $now);
            }

            $context = $this->buildTimeoutContext($game);
            $move = $this->recordTimeoutMove($game, $context->teamToPlay);

            $this->updateGameAfterTimeout($game, $context, $now);

            $this->em->flush();

            return new TimeoutResult($game, $move->getPly(), true, $now);
        } finally {
            $lock->release();
        }
    }

    private function fetchLiveGame(string $gameId): Game
    {
        $game = $this->games->get($gameId);
        if (!$game) {
            throw new NotFoundHttpException('game_not_found');
        }

        if (Game::STATUS_LIVE !== $game->getStatus()) {
            throw new ConflictHttpException('game_not_live');
        }

        return $game;
    }

    private function buildTimeoutContext(Game $game): TimeoutContext
    {
        $teamA = $this->teams->findOneByGameAndName($game, Team::NAME_A);
        $teamB = $this->teams->findOneByGameAndName($game, Team::NAME_B);

        if (!$teamA || !$teamB) {
            throw new NotFoundHttpException('teams_not_found');
        }

        $teamToPlay = Team::NAME_A === $game->getTurnTeam() ? $teamA : $teamB;
        $otherTeam = Team::NAME_A === $game->getTurnTeam() ? $teamB : $teamA;

        $order = $this->members->findActiveOrderedByTeam($teamToPlay);
        $orderCount = $order ? count($order) : 0;
        $currentIndex = $orderCount > 0 ? max(0, min($teamToPlay->getCurrentIndex(), $orderCount - 1)) : 0;

        return new TimeoutContext($teamToPlay, $otherTeam, $orderCount, $currentIndex);
    }

    private function recordTimeoutMove(Game $game, Team $teamToPlay): Move
    {
        $ply = $game->getPly() + 1;
        $move = new Move($game, $ply);
        $move
            ->setTeam($teamToPlay)
            ->setByUser(null)
            ->setUci(null)
            ->setSan(null)
            ->setFenAfter($game->getFen())
            ->setType(Move::TYPE_TIMEOUT);

        $this->moves->add($move);

        return $move;
    }

    private function updateGameAfterTimeout(Game $game, TimeoutContext $context, \DateTimeImmutable $now): void
    {
        $game->setPly($game->getPly() + 1);
        $game->setTurnTeam($context->teamToPlay->getName());
        $game->setTimeoutDecisionPending(true);

        $timedOutTeamName = Team::NAME_A === $context->teamToPlay->getName() ? Game::TEAM_A : Game::TEAM_B;

        if ($game->getLastTimeoutTeam() === $timedOutTeamName) {
            $game->incrementConsecutiveTimeouts();
        } else {
            $game->setConsecutiveTimeouts(1);
            $game->setLastTimeoutTeam($timedOutTeamName);
        }

        $game->setTimeoutTimedOutTeam($timedOutTeamName);
        $game->setTimeoutDecisionTeam(Game::TEAM_A === $timedOutTeamName ? Game::TEAM_B : Game::TEAM_A);
        $game->setFastModeEnabled(false);
        $game->setFastModeDeadline(null);
        $game->setTurnDeadline(null);
        $game->setUpdatedAt($now);

        $end = $this->endEvaluator->evaluateAndApply($game);
        if ($end['isOver']) {
            $game->setTurnDeadline(null);
        }
    }
}

final class TimeoutContext
{
    public function __construct(
        public readonly Team $teamToPlay,
        public readonly Team $otherTeam,
        public readonly int $orderCount,
        public readonly int $currentIndex,
    ) {
    }
}
