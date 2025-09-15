<?php

namespace App\Application\UseCase;

use App\Application\DTO\EnableFastModeInput;
use App\Application\DTO\EnableFastModeOutput;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Lock\LockFactory;

final class EnableFastModeHandler
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

    public function __invoke(EnableFastModeInput $in, User $user): EnableFastModeOutput
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

        try {
            // Vérifier que c'est bien le tour du joueur
            $teamA = $this->teams->findOneByGameAndName($game, Team::NAME_A);
            $teamB = $this->teams->findOneByGameAndName($game, Team::NAME_B);
            $teamToPlay = Team::NAME_A === $game->getTurnTeam() ? $teamA : $teamB;

            $order = $this->members->findActiveOrderedByTeam($teamToPlay);
            if (!$order) {
                throw new ConflictHttpException('no_players_in_team_to_play');
            }

            $idx = $teamToPlay->getCurrentIndex();
            $idx = max(0, min($idx, count($order) - 1));
            $mustPlay = $order[$idx];

            if ($mustPlay->getUser()->getId() !== $user->getId()) {
                throw new AccessDeniedHttpException('not_your_turn');
            }

            // Si le mode rapide est déjà activé, ne rien faire
            if ($game->isFastModeEnabled()) {
                return new EnableFastModeOutput(
                    $game->getId(),
                    true,
                    $game->getFastModeDeadline()?->getTimestamp() * 1000,
                    $game->getTurnDeadline()?->getTimestamp() * 1000
                );
            }

            // Activer le mode rapide : 1 minute
            $now = new \DateTimeImmutable();
            $fastModeDeadline = $now->modify('+1 minute');

            $game->setFastModeEnabled(true);
            $game->setFastModeDeadline($fastModeDeadline);
            $game->setUpdatedAt($now);

            $this->em->flush();

            return new EnableFastModeOutput(
                $game->getId(),
                true,
                $fastModeDeadline->getTimestamp() * 1000,
                $game->getTurnDeadline()?->getTimestamp() * 1000
            );
        } finally {
            $lock->release();
        }
    }
}
