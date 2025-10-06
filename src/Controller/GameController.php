<?php

namespace App\Controller;

use App\Application\DTO\ClaimVictoryInput;
use App\Application\DTO\CreateGameInput;
use App\Application\DTO\EnableFastModeInput;
use App\Application\DTO\JoinByCodeInput;
use App\Application\DTO\ListMovesInput;
use App\Application\DTO\MakeMoveInput;
use App\Application\DTO\MarkPlayerReadyInput;
use App\Application\DTO\ShowGameInput;
use App\Application\DTO\StartGameInput;
use App\Application\DTO\TimeoutDecisionInput;
use App\Application\DTO\TimeoutTickInput;
use App\Application\Service\PgnExporter;
use App\Application\UseCase\ClaimVictoryHandler;
use App\Application\UseCase\CreateGameHandler;
use App\Application\UseCase\DecideTimeoutHandler;
use App\Application\UseCase\EnableFastModeHandler;
use App\Application\UseCase\JoinByCodeHandler;
use App\Application\UseCase\ListMovesHandler;
use App\Application\UseCase\MakeMoveHandler;
use App\Application\UseCase\MarkPlayerReadyHandler;
use App\Application\UseCase\ShowGameHandler;
use App\Application\UseCase\StartGameHandler;
use App\Application\UseCase\TimeoutTickHandler;
use App\Domain\Repository\GameRepositoryInterface;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/games', name: 'game_')]
final class GameController extends AbstractController
{
    public function __construct(
        private CreateGameHandler $createGame,
        private JoinByCodeHandler $joinByCode,
        private StartGameHandler $startGame,
        private ShowGameHandler $showGame,
        private MakeMoveHandler $makeMove,
        private TimeoutTickHandler $timeoutTick,
        private DecideTimeoutHandler $decideTimeout,
        private ListMovesHandler $listMoves,
        private MarkPlayerReadyHandler $markPlayerReady,
        private EnableFastModeHandler $enableFastMode,
        private ClaimVictoryHandler $claimVictory,
        private HubInterface $mercureHub,
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $r): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $payload = $r->toArray();
        $mode = isset($payload['mode']) ? (string) $payload['mode'] : 'classic';
        $twoWolves = isset($payload['twoWolvesPerTeams']) ? (bool) $payload['twoWolvesPerTeams'] : false;

        $in = new CreateGameInput(
            creatorUserId: $user->getId(),
            turnDurationSec: isset($payload['turnDurationSec']) ? (int) $payload['turnDurationSec'] : 60,
            visibility: $payload['visibility'] ?? 'private',
            mode: $mode,
            twoWolvesPerTeams: $twoWolves,
        );

        $out = ($this->createGame)($in, $user);

        return $this->json([
            'gameId' => $out->gameId,
            'inviteCode' => $out->inviteCode,
            'turnDurationSec' => $out->turnDurationSec,
            'mode' => $mode,
            'twoWolvesPerTeams' => $twoWolves,
        ], 201);
    }

    #[Route('/join/{code}', name: 'join', methods: ['POST'])]
    public function join(string $code): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $in = new JoinByCodeInput(inviteCode: $code, userId: $user->getId() ?? '');
        $out = ($this->joinByCode)($in, $user);

        return $this->json([
            'ok' => true,
            'team' => $out->teamName,
            'position' => $out->position,
        ]);
    }

    // Démarrer la partie
    #[Route('/{id}/start', name: 'start', methods: ['POST'])]
    public function start(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $out = ($this->startGame)(new StartGameInput($id, $user->getId() ?? ''), $user);

        // Publier un événement Mercure pour notifier les abonnés
        $this->publishMercure($id, [
            'type' => 'game.started',
            'gameId' => $out->gameId,
            'turnTeam' => $out->turnTeam,
            'turnDeadline' => $out->turnDeadlineTs,
            'status' => $out->status,
        ]);

        return $this->json([
            'gameId' => $out->gameId,
            'status' => $out->status,
            'turnTeam' => $out->turnTeam,
            'turnDeadline' => $out->turnDeadlineTs,
        ]);
    }

    // GET /games/{id}
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id, Request $request): JsonResponse
    {
        $out = ($this->showGame)(new ShowGameInput($id));

        // ETag based on stable, frequently changing fields
        $etagBase = implode(':', [
            $out->id,
            $out->status,
            $out->ply,
            $out->turnTeam,
            (int) $out->turnDeadlineTs,
        ]);
        $etag = 'W/"'.substr(sha1($etagBase), 0, 16).'"';

        $response = $this->json([
            'id' => $out->id,
            'status' => $out->status,
            'fen' => $out->fen,
            'ply' => $out->ply,
            'turnTeam' => $out->turnTeam,
            'turnDeadline' => $out->turnDeadlineTs,
            'handBrainCurrentRole' => $out->handBrainCurrentRole,
            'handBrainPieceHint' => $out->handBrainPieceHint,
            'handBrainBrainMemberId' => $out->handBrainBrainMemberId,
            'handBrainHandMemberId' => $out->handBrainHandMemberId,
            'teams' => [
                'A' => $out->teamA,
                'B' => $out->teamB,
            ],
        ]);
        $response->setEtag($etag);
        $response->headers->set('Cache-Control', 'private, must-revalidate');
        if ($response->isNotModified($request)) {
            return $response; // 304 Not Modified
        }

        return $response;
    }

    // POST /games/{id}/move
    #[Route('/{id}/move', name: 'move', methods: ['POST'])]
    public function move(string $id, Request $r, GameRepositoryInterface $gameRepo): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $payload = $r->toArray();
        $uci = (string) ($payload['uci'] ?? '');
        $out = ($this->makeMove)(new MakeMoveInput($id, $uci, $user->getId() ?? ''), $user);

        $game = $gameRepo->get($out->gameId);

        $response = $this->json([
            'gameId' => $out->gameId,
            'ply' => $out->ply,
            'turnTeam' => $out->turnTeam,
            'turnDeadline' => $out->turnDeadlineTs,
            'fen' => $out->fen,
            'status' => $game?->getStatus(),
            'result' => $game?->getResult(),
            'handBrainCurrentRole' => $out->handBrainCurrentRole,
            'handBrainPieceHint' => $out->handBrainPieceHint,
            'handBrainBrainMemberId' => $out->handBrainBrainMemberId,
            'handBrainHandMemberId' => $out->handBrainHandMemberId,
        ], 201);

        // Publier un événement Mercure pour notifier les abonnés
        $this->publishMercure($id, [
            'type' => 'game.move',
            'gameId' => $out->gameId,
            'uci' => $uci,
            'ply' => $out->ply,
            'turnTeam' => $out->turnTeam,
            'turnDeadline' => $out->turnDeadlineTs,
            'fen' => $out->fen,
            'status' => $game?->getStatus(),
            'result' => $game?->getResult(),
            'handBrainCurrentRole' => $out->handBrainCurrentRole,
            'handBrainPieceHint' => $out->handBrainPieceHint,
            'handBrainBrainMemberId' => $out->handBrainBrainMemberId,
            'handBrainHandMemberId' => $out->handBrainHandMemberId,
        ]);

        return $response;
    }

    // POST /games/{id}/tick
    #[Route('/{id}/tick', name: 'tick', methods: ['POST'])]
    public function tick(string $id, GameRepositoryInterface $gameRepo): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $out = ($this->timeoutTick)(new TimeoutTickInput($id, $user->getId() ?? ''), $user);

        $game = $gameRepo->get($out->gameId);

        $response = $this->json([
            'gameId' => $out->gameId,
            'timedOutApplied' => $out->timedOutApplied,
            'ply' => $out->ply,
            'turnTeam' => $out->turnTeam,
            'turnDeadline' => $out->turnDeadlineTs,
            'fen' => $out->fen,
            'status' => $game?->getStatus(),
            'result' => $game?->getResult(),
            'handBrainCurrentRole' => $out->handBrainCurrentRole,
            'handBrainPieceHint' => $out->handBrainPieceHint,
            'handBrainBrainMemberId' => $out->handBrainBrainMemberId,
            'handBrainHandMemberId' => $out->handBrainHandMemberId,
        ], $out->timedOutApplied ? 201 : 200);

        // Publier un événement Mercure seulement si un timeout a été appliqué (changement réel)
        if ($out->timedOutApplied) {
            $this->publishMercure($id, [
                'type' => 'game.timeout',
                'gameId' => $out->gameId,
                'ply' => $out->ply,
                'turnTeam' => $out->turnTeam,
                'turnDeadline' => $out->turnDeadlineTs,
                'fen' => $out->fen,
                'status' => $game?->getStatus(),
                'result' => $game?->getResult(),
                'handBrainCurrentRole' => $out->handBrainCurrentRole,
                'handBrainPieceHint' => $out->handBrainPieceHint,
                'handBrainBrainMemberId' => $out->handBrainBrainMemberId,
                'handBrainHandMemberId' => $out->handBrainHandMemberId,
                // client can refetch state to get detailed decision info
            ]);
        }

        return $response;
    }

    // GET /games/{id}/moves
    #[Route('/{id}/moves', name: 'moves', methods: ['GET'])]
    public function moves(string $id, Request $r): JsonResponse
    {
        $since = $r->query->has('since') ? (int) $r->query->get('since') : null;
        $out = ($this->listMoves)(new ListMovesInput($id, $since));

        /** @var array<int, array<string, mixed>> $moves */
        $moves = $out->moves;
        $moves = \array_values(\array_filter($moves, static function (array $m): bool {
            $type = isset($m['type']) ? (string) $m['type'] : 'normal';
            $san = isset($m['san']) ? (string) $m['san'] : '';
            $uci = isset($m['uci']) ? (string) $m['uci'] : '';

            // Keep timeout-pass or any move that has san or uci
            return 'timeout-pass' === $type || '' !== $san || '' !== $uci;
        }));

        // Normalise team name to 'A'/'B' strings when possible to stabilise UI classes
        $moves = \array_map(static function (array $m): array {
            if (isset($m['team']) && \is_array($m['team'])) {
                $name = $m['team']['name'] ?? $m['team']['teamName'] ?? null;
                if ($name) {
                    $m['team'] = (string) $name; // e.g., 'A' or 'B'
                }
            }

            return $m;
        }, $moves);

        return $this->json([
            'gameId' => $out->gameId,
            'moves' => $moves,
        ]);
    }

    // POST /games/{id}/ready
    #[Route('/{id}/ready', name: 'mark_ready', methods: ['POST'])]
    public function markReady(string $id, Request $r): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $payload = $r->toArray();
        $ready = isset($payload['ready']) ? (bool) $payload['ready'] : true;

        $in = new MarkPlayerReadyInput($id, $user->getId() ?? '', $ready);
        $out = ($this->markPlayerReady)($in, $user);

        $response = $this->json([
            'gameId' => $out->gameId,
            'userId' => $out->userId,
            'ready' => $out->ready,
            'allPlayersReady' => $out->allPlayersReady,
            'readyPlayersCount' => $out->readyPlayersCount,
            'totalPlayersCount' => $out->totalPlayersCount,
        ]);

        // Publier un événement Mercure pour synchroniser le lobby
        $this->publishMercure($id, [
            'type' => 'game.ready_changed',
            'gameId' => $out->gameId,
            'userId' => $out->userId,
            'ready' => $out->ready,
            'allPlayersReady' => $out->allPlayersReady,
        ]);

        return $response;
    }

    // POST /games/{id}/enable-fast-mode - Activer le mode rapide (1 minute)
    #[Route('/{id}/enable-fast-mode', name: 'enable_fast_mode', methods: ['POST'])]
    public function enableFastMode(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $in = new EnableFastModeInput($id, $user->getId() ?? '');
        $out = ($this->enableFastMode)($in, $user);

        $response = $this->json([
            'gameId' => $out->gameId,
            'fastModeEnabled' => $out->fastModeEnabled,
            'fastModeDeadline' => $out->fastModeDeadlineTs,
            'turnDeadline' => $out->turnDeadlineTs,
        ], 200);

        $this->publishMercure($id, [
            'type' => 'game.fast_mode',
            'gameId' => $out->gameId,
            'fastModeEnabled' => $out->fastModeEnabled,
            'fastModeDeadline' => $out->fastModeDeadlineTs,
            'turnDeadline' => $out->turnDeadlineTs,
        ]);

        return $response;
    }

    // POST /games/{id}/claim-victory - Revendiquer la victoire après timeouts consécutifs
    #[Route('/{id}/claim-victory', name: 'claim_victory', methods: ['POST'])]
    public function claimVictory(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $in = new ClaimVictoryInput($id, $user->getId() ?? '');
        $out = ($this->claimVictory)($in, $user);

        $response = $this->json([
            'gameId' => $out->gameId,
            'claimed' => $out->claimed,
            'result' => $out->result,
            'status' => $out->status,
            'winnerTeam' => $out->winnerTeam,
        ], 200);

        $this->publishMercure($id, [
            'type' => 'game.claimed',
            'gameId' => $out->gameId,
            'result' => $out->result,
            'status' => $out->status,
            'winnerTeam' => $out->winnerTeam,
        ]);

        return $response;
    }

    /**
     * Publie un événement Mercure sur le topic de la partie.
     */
    private function publishMercure(string $gameId, array $data): void
    {
        try {
            $topic = sprintf('/games/%s', $gameId);
            $update = new Update(
                topics: $topic,
                data: json_encode($data, JSON_THROW_ON_ERROR)
            );
            $this->mercureHub->publish($update);
        } catch (\Throwable $e) {
            // Ne pas casser la requête si Mercure échoue en dev
        }
    }

    // POST /games/{id}/timeout-decision
    #[Route('/{id}/timeout-decision', name: 'timeout_decision', methods: ['POST'])]
    public function timeoutDecision(string $id, Request $r): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $payload = $r->toArray();
        $decision = (string) ($payload['decision'] ?? ''); // 'end' | 'allow_next'

        $out = ($this->decideTimeout)(new TimeoutDecisionInput($id, $user->getId() ?? '', $decision), $user);

        $response = $this->json([
            'gameId' => $out->gameId,
            'status' => $out->status,
            'result' => $out->result,
            'pending' => $out->pending,
            'turnTeam' => $out->turnTeam,
            'turnDeadline' => $out->turnDeadlineTs,
        ]);

        // Inform clients
        $this->publishMercure($id, [
            'type' => 'game.timeout_decision',
            'gameId' => $out->gameId,
            'status' => $out->status,
            'result' => $out->result,
            'pending' => $out->pending,
            'turnTeam' => $out->turnTeam,
            'turnDeadline' => $out->turnDeadlineTs,
        ]);

        return $response;
    }

    // GET /games/{id}/state - Endpoint pour l'actualisation automatique
    #[Route('/{id}/state', name: 'state', methods: ['GET'])]
    public function state(string $id, GameRepositoryInterface $gameRepo, Request $request): JsonResponse
    {
        try {
            // Récupérer l'état complet de la partie
            $gameOut = ($this->showGame)(new ShowGameInput($id));
            $game = $gameRepo->get($id);

            // Déterminer le joueur actuel
            $currentPlayer = null;
            if ('live' === $gameOut->status && $game->getCurrentMembership()) {
                $membership = $game->getCurrentMembership();
                $currentPlayer = [
                    'id' => $membership->getId(),
                    'userId' => $membership->getUser()->getId(),
                    'displayName' => $membership->getUser()->getDisplayName(),
                    'teamName' => $membership->getTeam()->getName(),
                ];
            }

            // Informations sur le mode de chronométrage
            $fastModeDeadline = $game->getFastModeDeadline();
            $fastModeInfo = [
                'enabled' => $game->isFastModeEnabled(),
                'deadline' => $fastModeDeadline ? $fastModeDeadline->getTimestamp() * 1000 : null,
            ];

            $effectiveDeadline = $game->getEffectiveDeadline();
            $turnDeadline = $game->getTurnDeadline();
            $timingInfo = [
                'mode' => $game->isFastModeEnabled() ? 'fast' : 'free',
                'effectiveDeadline' => $effectiveDeadline ? $effectiveDeadline->getTimestamp() * 1000 : null,
                'turnDeadline' => $turnDeadline ? $turnDeadline->getTimestamp() * 1000 : null,
                'fastMode' => $fastModeInfo,
            ];

            $handBrainInfo = [
                'currentRole' => $game->getHandBrainCurrentRole(),
                'pieceHint' => $game->getHandBrainPieceHint(),
                'brainMemberId' => $game->getHandBrainBrainMemberId(),
                'handMemberId' => $game->getHandBrainHandMemberId(),
            ];

            // Informations de revendication de victoire
            $claimInfo = [
                'canClaim' => $game->canClaimVictory(),
                'claimTeam' => $game->getClaimVictoryTeam(),
                'consecutiveTimeouts' => $game->getConsecutiveTimeouts(),
                'lastTimeoutTeam' => $game->getLastTimeoutTeam(),
            ];

            // Informations sur décision de timeout en attente
            $timeoutDecision = [
                'pending' => $game->isTimeoutDecisionPending(),
                'decisionTeam' => $game->getTimeoutDecisionTeam(),
                'timedOutTeam' => $game->getTimeoutTimedOutTeam(),
            ];

            // Compute ETag BEFORE heavy payload (moves)
            $etagBase = implode(':', [
                $gameOut->id,
                $gameOut->status,
                $gameOut->ply,
                $gameOut->turnTeam,
                (int) $gameOut->turnDeadlineTs,
                (int) ($game->getEffectiveDeadline()?->getTimestamp() ?? 0),
                (int) ($game->getFastModeDeadline()?->getTimestamp() ?? 0),
                (int) $game->getConsecutiveTimeouts(),
                (string) $game->getLastTimeoutTeam(),
                (string) $game->getResult(),
                (string) $game->getHandBrainCurrentRole(),
                (string) $game->getHandBrainPieceHint(),
                (string) $game->getHandBrainBrainMemberId(),
                (string) $game->getHandBrainHandMemberId(),
            ]);
            $etag = 'W/"'.substr(sha1($etagBase), 0, 16).'"';

            // Prepare response with headers first
            $payload = [
                'gameId' => $gameOut->id,
                'status' => $gameOut->status,
                'result' => $game->getResult(),
                'fen' => $gameOut->fen,
                'ply' => $gameOut->ply,
                'turnTeam' => $gameOut->turnTeam,
                'turnDeadline' => $gameOut->turnDeadlineTs,
                'timing' => $timingInfo,
                'currentPlayer' => $currentPlayer,
                // moves will be injected below only if modified
                'teams' => [
                    'A' => $gameOut->teamA,
                    'B' => $gameOut->teamB,
                ],
                'claimVictory' => $claimInfo,
                'timeoutDecision' => $timeoutDecision,
                'lastUpdate' => time(),
                'handBrain' => $handBrainInfo,
                'handBrainCurrentRole' => $handBrainInfo['currentRole'],
                'handBrainPieceHint' => $handBrainInfo['pieceHint'],
                'handBrainBrainMemberId' => $handBrainInfo['brainMemberId'],
                'handBrainHandMemberId' => $handBrainInfo['handMemberId'],
            ];

            $response = $this->json($payload);
            $response->setEtag($etag);
            $response->headers->set('Cache-Control', 'private, must-revalidate');
            if ($response->isNotModified($request)) {
                return $response; // 304 Not Modified, avoid computing moves
            }

            // Only now fetch moves (heavier) because content changed
            $movesOut = ($this->listMoves)(new ListMovesInput($id));
            $data = json_decode($response->getContent() ?: '{}', true);
            $data['moves'] = $movesOut->moves;
            $response->setData($data);

            return $response;
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Impossible de récupérer l\'état de la partie',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // GET /games/{id}/pgn - Export PGN de la partie
    #[Route('/{id}/pgn', name: 'pgn', methods: ['GET'])]
    public function pgn(string $id, GameRepositoryInterface $gameRepo, PgnExporter $pgn): Response
    {
        $game = $gameRepo->get($id);
        if (!$game) {
            throw $this->createNotFoundException('game_not_found');
        }

        $content = $pgn->export($game);

        return new Response(
            $content,
            200,
            [
                'Content-Type' => 'application/x-chess-pgn; charset=utf-8',
                'Cache-Control' => 'no-cache',
            ]
        );
    }
}
