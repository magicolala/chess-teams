<?php

namespace App\Application\Service\Game;

use App\Application\DTO\MakeMoveInput;
use App\Application\Port\ChessEngineInterface;
use App\Application\Service\Game\DTO\MoveResult;
use App\Application\Service\Game\HandBrain\HandBrainMoveInspector;
use App\Application\Service\Game\Traits\HandBrainTurnHelper;
use App\Application\Service\GameEndEvaluator;
use App\Application\Service\Werewolf\WerewolfVoteService;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\MoveRepositoryInterface;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\Move;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Lock\LockFactory;

final class GameMoveService implements GameMoveServiceInterface
{
    use HandBrainTurnHelper;

    public function __construct(
        private readonly GameRepositoryInterface $games,
        private readonly TeamRepositoryInterface $teams,
        private readonly TeamMemberRepositoryInterface $members,
        private readonly MoveRepositoryInterface $moves,
        private readonly ChessEngineInterface $engine,
        private readonly HandBrainMoveInspector $handBrainInspector,
        private readonly LockFactory $lockFactory,
        private readonly EntityManagerInterface $em,
        private readonly GameEndEvaluator $endEvaluator,
        private readonly WerewolfVoteService $werewolfVote,
    ) {
    }

    protected function getTeamMemberRepository(): TeamMemberRepositoryInterface
    {
        return $this->members;
    }

    public function play(MakeMoveInput $input, User $player): MoveResult
    {
        $game = $this->fetchLiveGame($input->gameId);

        $lock = $this->lockFactory->createLock('game:'.$game->getId(), 5.0);
        if (!$lock->acquire()) {
            throw new ConflictHttpException('locked');
        }

        try {
            $context = $this->buildTurnContext($game, $player);

            $now = new \DateTimeImmutable();
            $this->guardDeadline($game, $now);

            $uci = $this->sanitizeUci($input->uci);
            $this->guardHandBrainMove($game, $context, $uci);
            $engineResult = $this->applyMoveWithEngine($game, $uci);

            $move = $this->recordMove($game, $context->teamToPlay, $player, $uci, $engineResult['san'], $engineResult['fenAfter']);

            $this->advanceGameState($game, $context, $move->getPly(), $now, $engineResult['san'], $engineResult['fenAfter']);

            $this->em->flush();

            return new MoveResult($game, $move->getPly(), $now);
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

        if ($game->isTimeoutDecisionPending()) {
            throw new ConflictHttpException('timeout_decision_pending');
        }

        return $game;
    }

    private function buildTurnContext(Game $game, User $player): TurnContext
    {
        $teamA = $this->teams->findOneByGameAndName($game, Team::NAME_A);
        $teamB = $this->teams->findOneByGameAndName($game, Team::NAME_B);

        if (!$teamA || !$teamB) {
            throw new NotFoundHttpException('teams_not_found');
        }

        $teamToPlay = Team::NAME_A === $game->getTurnTeam() ? $teamA : $teamB;
        $otherTeam = Team::NAME_A === $game->getTurnTeam() ? $teamB : $teamA;

        $order = $this->members->findActiveOrderedByTeam($teamToPlay);
        if (!$order) {
            throw new ConflictHttpException('no_players_in_team_to_play');
        }

        $idx = max(0, min($teamToPlay->getCurrentIndex(), count($order) - 1));
        /** @var TeamMember $mustPlay */
        $mustPlay = $order[$idx];

        if ($mustPlay->getUser()->getId() !== $player->getId()) {
            throw new AccessDeniedHttpException('not_your_turn_in_team');
        }

        return new TurnContext($teamToPlay, $otherTeam, $order, $idx);
    }

    private function guardDeadline(Game $game, \DateTimeImmutable $now): void
    {
        $deadline = $game->getEffectiveDeadline();
        if ($deadline && $now > $deadline) {
            throw new ConflictHttpException('turn_expired');
        }
    }

    private function sanitizeUci(?string $uci): string
    {
        $value = trim((string) $uci);
        if ('' === $value || !preg_match('/^[a-h][1-8][a-h][1-8][qrbn]?$/i', $value)) {
            throw new UnprocessableEntityHttpException('invalid_uci');
        }

        return $value;
    }

    /**
     * @return array{fenAfter: string, san: string}
     */
    private function applyMoveWithEngine(Game $game, string $uci): array
    {
        try {
            $result = $this->engine->applyUci($game->getFen(), $uci);
        } catch (\InvalidArgumentException $e) {
            throw new UnprocessableEntityHttpException('illegal_move');
        }

        $fenAfter = (string) $result['fenAfter'];
        if (!array_key_exists('san', $result)) {
            $san = '';
        } else {
            $san = trim((string) $result['san']);
        }

        if ('' === $san) {
            $san = $uci;
        }

        return [
            'fenAfter' => $fenAfter,
            'san' => $san,
        ];
    }

    private function guardHandBrainMove(Game $game, TurnContext $context, string $uci): void
    {
        if ('hand_brain' !== $game->getMode()) {
            return;
        }

        $hint = trim((string) $game->getHandBrainPieceHint());
        if ('' === $hint) {
            throw new UnprocessableEntityHttpException('hand_brain_missing_hint');
        }

        $handMemberId = $game->getHandBrainHandMemberId();
        $currentPlayer = $context->order[$context->index] ?? null;

        if (!$currentPlayer || null === $handMemberId || $currentPlayer->getId() !== $handMemberId) {
            throw new AccessDeniedHttpException('hand_brain_not_assigned_hand');
        }

        $pieceType = $this->handBrainInspector->resolvePieceType($game->getFen(), $uci);
        if (null === $pieceType) {
            throw new UnprocessableEntityHttpException('hand_brain_unknown_piece');
        }

        if ($pieceType !== strtolower($hint)) {
            throw new UnprocessableEntityHttpException('hand_brain_hint_mismatch');
        }
    }

    private function recordMove(Game $game, Team $team, User $byUser, string $uci, string $san, string $fenAfter): Move
    {
        $ply = $game->getPly() + 1;
        $move = new Move($game, $ply);
        $move
            ->setTeam($team)
            ->setByUser($byUser)
            ->setUci($uci)
            ->setSan($san)
            ->setFenAfter($fenAfter);

        $this->moves->add($move);

        return $move;
    }

    private function advanceGameState(Game $game, TurnContext $context, int $ply, \DateTimeImmutable $now, string $san, string $fenAfter): void
    {
        $game->setFen($fenAfter);
        $game->setPly($ply);

        $teamToPlay = $context->teamToPlay;
        $orderSize = max(1, $context->orderSize);
        $teamToPlay->setCurrentIndex(($context->index + 1) % $orderSize);

        $game->setTurnTeam($context->otherTeam->getName());

        if ('hand_brain' === $game->getMode()) {
            $game->setHandBrainPieceHint(null);
            $this->refreshHandBrainStateForTeam($game, $context->otherTeam);
        }

        $game->resetConsecutiveTimeouts();
        $game->setFastModeEnabled(false);
        $game->setFastModeDeadline(null);

        $deadline = $now->modify('+14 days');
        $game->setTurnDeadline($deadline);
        $game->setUpdatedAt($now);

        if (false !== strpos($san, '#')) {
            $game->setResult($teamToPlay->getName().'#');
            $game->setStatus(Game::STATUS_FINISHED);
            $game->setTurnDeadline(null);
            $game->setFastModeDeadline(null);
            if ('werewolf' === $game->getMode()) {
                $this->werewolfVote->openVote($game);
            }

            return;
        }

        $end = $this->endEvaluator->evaluateAndApply($game);
        if ($end['isOver']) {
            $game->setTurnDeadline(null);
            $game->setFastModeDeadline(null);
            if ('werewolf' === $game->getMode()) {
                $this->werewolfVote->openVote($game);
            }
        }
    }
}

/**
 * @internal small immutable helper carrying turn-related context
 */
final class TurnContext
{
    public readonly Team $teamToPlay;
    public readonly Team $otherTeam;
    /** @var TeamMember[] */
    public readonly array $order;
    public readonly int $index;
    public readonly int $orderSize;

    /**
     * @param TeamMember[] $order
     */
    public function __construct(Team $teamToPlay, Team $otherTeam, array $order, int $index)
    {
        $this->teamToPlay = $teamToPlay;
        $this->otherTeam = $otherTeam;
        $this->order = $order;
        $this->index = $index;
        $this->orderSize = count($order);
    }
}
