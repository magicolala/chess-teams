<?php

namespace App\Application\UseCase;

use App\Application\DTO\ClaimVictoryInput;
use App\Application\DTO\ClaimVictoryOutput;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Entity\Game;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Lock\LockFactory;

final class ClaimVictoryHandler
{
    public function __construct(
        private GameRepositoryInterface $games,
        private TeamMemberRepositoryInterface $members,
        #[Autowire(service: 'lock.factory')]
        private LockFactory $lockFactory,
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(ClaimVictoryInput $in, User $requestedBy): ClaimVictoryOutput
    {
        $game = $this->games->get($in->gameId);
        if (!$game) {
            throw new NotFoundHttpException('game_not_found');
        }

        if (Game::STATUS_LIVE !== $game->getStatus()) {
            throw new ConflictHttpException('game_not_live');
        }

        if (!$game->canClaimVictory()) {
            throw new ConflictHttpException('cannot_claim_victory');
        }

        $lock = $this->lockFactory->createLock('game:'.$game->getId(), 5.0);
        if (!$lock->acquire()) {
            throw new ConflictHttpException('locked');
        }

        try {
            // Vérifier que l'utilisateur fait partie de l'équipe qui peut revendiquer
            $claimTeamName = $game->getClaimVictoryTeam();
            $userMembership = $this->members->findOneByGameAndUser($game, $requestedBy);

            if (!$userMembership) {
                throw new AccessDeniedHttpException('player_not_in_game');
            }

            $userTeamName = Team::NAME_A === $userMembership->getTeam()->getName() ? Game::TEAM_A : Game::TEAM_B;
            if ($userTeamName !== $claimTeamName) {
                throw new AccessDeniedHttpException('not_your_team_to_claim');
            }

            // Appliquer la revendication de victoire
            $loserTeam = $game->getLastTimeoutTeam();
            $winnerTeam = $claimTeamName;
            $result = $winnerTeam.'+'.$loserTeam.'timeout'; // Ex: "B+Atimeout" (B gagne par timeout de A)

            $game->setResult($result);
            $game->setStatus(Game::STATUS_FINISHED);
            $game->setTurnDeadline(null);
            $game->setUpdatedAt(new \DateTimeImmutable());

            $this->em->flush();

            return new ClaimVictoryOutput(
                $game->getId(),
                true,
                $result,
                Game::STATUS_FINISHED,
                $winnerTeam
            );
        } finally {
            $lock->release();
        }
    }
}
