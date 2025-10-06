<?php

namespace App\Application\Service\Game\HandBrain;

use App\Application\Service\Game\HandBrain\DTO\HandBrainState;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Entity\Game;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class HandBrainHintService
{
    private const ALLOWED_HINTS = ['pawn', 'knight', 'bishop', 'rook', 'queen', 'king'];

    public function __construct(
        private readonly GameRepositoryInterface $games,
        private readonly TeamMemberRepositoryInterface $members,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function setHint(string $gameId, string $piece, User $requestedBy): HandBrainState
    {
        $game = $this->games->get($gameId);
        if (!$game) {
            throw new NotFoundHttpException('game_not_found');
        }

        if (Game::STATUS_LIVE !== $game->getStatus()) {
            throw new ConflictHttpException('game_not_live');
        }

        if ('hand_brain' !== $game->getMode()) {
            throw new ConflictHttpException('hand_brain_mode_disabled');
        }

        if ('brain' !== $game->getHandBrainCurrentRole()) {
            throw new ConflictHttpException('hand_brain_not_waiting_brain');
        }

        $membership = $this->members->findOneByGameAndUser($game, $requestedBy);
        if (!$membership || !$membership->isActive()) {
            throw new AccessDeniedHttpException('hand_brain_not_participant');
        }

        $brainMemberId = $game->getHandBrainBrainMemberId();
        if (null === $brainMemberId || $membership->getId() !== $brainMemberId) {
            throw new AccessDeniedHttpException('hand_brain_not_assigned_brain');
        }

        if ($membership->getTeam()->getName() !== $game->getTurnTeam()) {
            throw new AccessDeniedHttpException('hand_brain_not_team_turn');
        }

        $normalized = strtolower(trim($piece));
        if ('' === $normalized || !in_array($normalized, self::ALLOWED_HINTS, true)) {
            throw new UnprocessableEntityHttpException('hand_brain_invalid_hint');
        }

        $game
            ->setHandBrainPieceHint($normalized)
            ->setHandBrainCurrentRole('hand')
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return new HandBrainState(
            $game->getId(),
            $game->getHandBrainCurrentRole(),
            $game->getHandBrainPieceHint(),
            $game->getHandBrainBrainMemberId(),
            $game->getHandBrainHandMemberId(),
        );
    }
}
