<?php

namespace App\Application\UseCase;

use App\Application\DTO\MarkPlayerReadyInput;
use App\Application\DTO\MarkPlayerReadyOutput;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Entity\Game;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class MarkPlayerReadyHandler
{
    public function __construct(
        private GameRepositoryInterface $games,
        private TeamMemberRepositoryInterface $members,
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(MarkPlayerReadyInput $in, User $requestedBy): MarkPlayerReadyOutput
    {
        $game = $this->games->get($in->gameId);
        if (!$game) {
            throw new NotFoundHttpException('game_not_found');
        }

        // Vérifier que le jeu est encore en lobby (on ne peut marquer comme prêt que dans le lobby)
        if (Game::STATUS_LOBBY !== $game->getStatus()) {
            throw new ConflictHttpException('game_not_in_lobby');
        }

        // Trouver le team member pour ce joueur dans ce jeu
        $teamMember = $this->members->findOneByGameAndUser($game, $requestedBy);
        if (!$teamMember) {
            throw new AccessDeniedHttpException('player_not_in_game');
        }

        // Vérifier que le joueur est actif
        if (!$teamMember->isActive()) {
            throw new ConflictHttpException('player_not_active');
        }

        // Marquer le joueur comme prêt ou pas prêt
        $teamMember->setReadyToStart($in->ready);

        $this->em->flush();

        // Calculer les statistiques
        $readyCount = $this->members->countReadyByGame($game);
        $totalCount = $this->members->countActiveByGame($game);
        $allReady = $this->members->areAllActivePlayersReady($game);

        return new MarkPlayerReadyOutput(
            gameId: $game->getId(),
            userId: $requestedBy->getId(),
            ready: $teamMember->isReadyToStart(),
            allPlayersReady: $allReady,
            readyPlayersCount: $readyCount,
            totalPlayersCount: $totalCount,
        );
    }
}
