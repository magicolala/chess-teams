<?php

namespace App\Controller;

use App\Application\DTO\CreateGameInput;
use App\Application\DTO\JoinByCodeInput;
use App\Application\DTO\ShowGameInput;
use App\Application\DTO\StartGameInput;
use App\Application\UseCase\CreateGameHandler;
use App\Application\UseCase\JoinByCodeHandler;
use App\Application\UseCase\ShowGameHandler;
use App\Application\UseCase\StartGameHandler;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/games', name: 'game_')]
final class GameController extends AbstractController
{
    public function __construct(
        private CreateGameHandler $createGame,
        private JoinByCodeHandler $joinByCode,
        private StartGameHandler $startGame,
        private ShowGameHandler $showGame,
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $r): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $payload = $r->toArray();
        $in = new CreateGameInput(
            creatorUserId: $user->getId(),
            turnDurationSec: isset($payload['turnDurationSec']) ? (int) $payload['turnDurationSec'] : 60,
            visibility: $payload['visibility'] ?? 'private'
        );

        $out = ($this->createGame)($in, $user);

        return $this->json([
            'gameId' => $out->gameId,
            'inviteCode' => $out->inviteCode,
            'turnDurationSec' => $out->turnDurationSec,
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

        return $this->json([
            'gameId' => $out->gameId,
            'status' => $out->status,
            'turnTeam' => $out->turnTeam,
            'turnDeadline' => $out->turnDeadlineTs,
        ]);
    }

    // GET /games/{id}
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $out = ($this->showGame)(new ShowGameInput($id));

        return $this->json([
            'id' => $out->id,
            'status' => $out->status,
            'fen' => $out->fen,
            'ply' => $out->ply,
            'turnTeam' => $out->turnTeam,
            'turnDeadline' => $out->turnDeadlineTs,
            'teams' => [
                'A' => $out->teamA,
                'B' => $out->teamB,
            ],
        ]);
    }
}
