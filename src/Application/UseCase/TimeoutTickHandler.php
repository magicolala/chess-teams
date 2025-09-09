<?php

namespace App\Application\UseCase;

use App\Application\DTO\TimeoutTickInput;
use App\Application\DTO\TimeoutTickOutput;
use App\Application\Service\GameEndEvaluator;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\MoveRepositoryInterface;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\Move;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Lock\LockFactory;

final class TimeoutTickHandler
{
    public function __construct(
        private GameRepositoryInterface $games,
        private TeamRepositoryInterface $teams,
        private TeamMemberRepositoryInterface $members,
        private MoveRepositoryInterface $moves,
        #[Autowire(service: 'lock.factory')]
        private LockFactory $lockFactory,
        private EntityManagerInterface $em,
        private GameEndEvaluator $endEvaluator,
    ) {
    }

    public function __invoke(TimeoutTickInput $in, User $requestedBy): TimeoutTickOutput
    {
        $game = $this->games->get($in->gameId);
        if (!$game) {
            throw new NotFoundHttpException('game_not_found');
        }
        if (Game::STATUS_LIVE !== $game->getStatus()) {
            throw new ConflictHttpException('game_not_live');
        }

        $lock = $this->lockFactory->createLock('game:'.$game->getId(), 5.0);
        if (!$lock->acquire()) {
            throw new ConflictHttpException('locked');
        }
        if (Game::STATUS_FINISHED === $game->getStatus()) {
            throw new ConflictHttpException('game_finished');
        }

        try {
            $now = new \DateTimeImmutable();
            $effectiveDeadline = $game->getEffectiveDeadline();

            // Si pas de délai ou si le délai n'est pas dépassé, pas de timeout
            if (!$effectiveDeadline || $now <= $effectiveDeadline) {
                return new TimeoutTickOutput(
                    $game->getId(),
                    false,
                    $game->getPly(),
                    $game->getTurnTeam(),
                    ($effectiveDeadline?->getTimestamp() ?? $now->getTimestamp()) * 1000,
                    $game->getFen()
                );
            }

            $teamA = $this->teams->findOneByGameAndName($game, Team::NAME_A);
            $teamB = $this->teams->findOneByGameAndName($game, Team::NAME_B);
            $teamToPlay = Team::NAME_A === $game->getTurnTeam() ? $teamA : $teamB;
            $othersTeam = Team::NAME_A === $game->getTurnTeam() ? $teamB : $teamA;

            $order = $this->members->findActiveOrderedByTeam($teamToPlay);
            if (!$order) {
                $orderCount = 0;
            } else {
                $orderCount = count($order);
                $idx = max(0, min($teamToPlay->getCurrentIndex(), $orderCount - 1));
            }

            $ply = $game->getPly() + 1;
            $mv = new Move($game, $ply);
            $mv->setTeam($teamToPlay)
                ->setByUser(null)
                ->setUci(null)
                ->setSan(null)
                ->setFenAfter($game->getFen())
                ->setType(Move::TYPE_TIMEOUT)
            ;
            $this->moves->add($mv);

            // Ne pas avancer l'index tout de suite: l'adversaire choisira s'il autorise le joueur suivant à jouer.

            // Gestion des timeouts consécutifs
            $currentTeamName = Team::NAME_A === $teamToPlay->getName() ? Game::TEAM_A : Game::TEAM_B;
            if ($game->getLastTimeoutTeam() === $currentTeamName) {
                // Même équipe que le dernier timeout
                $game->incrementConsecutiveTimeouts();
            } else {
                // Équipe différente ou premier timeout
                $game->setConsecutiveTimeouts(1);
                $game->setLastTimeoutTeam($currentTeamName);
            }

            $game->setPly($ply);
            // Le tour reste à l'équipe qui a dépassé le temps jusqu'à décision de l'adversaire
            $game->setTurnTeam($teamToPlay->getName());

            // Marquer la décision en attente
            $game->setTimeoutDecisionPending(true);
            $game->setTimeoutTimedOutTeam($currentTeamName);
            $game->setTimeoutDecisionTeam($currentTeamName === Game::TEAM_A ? Game::TEAM_B : Game::TEAM_A);

            // Suspendre les délais pendant la décision
            $game->setFastModeEnabled(false);
            $game->setFastModeDeadline(null);
            $game->setTurnDeadline(null);
            $game->setUpdatedAt($now);

            $end = $this->endEvaluator->evaluateAndApply($game);
            if ($end['isOver']) {
                $game->setTurnDeadline(null);
            }

            $this->em->flush();

            return new TimeoutTickOutput(
                $game->getId(),
                true,
                $ply,
                $game->getTurnTeam(),
                $game->getTurnDeadline()?->getTimestamp() * 1000 ?? 0,
                $game->getFen()
            );
        } finally {
            $lock->release();
        }
    }
}
