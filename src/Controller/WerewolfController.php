<?php

namespace App\Controller;

use App\Application\Service\Werewolf\WerewolfVoteService;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Entity\Game;
use App\Entity\GameRole;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/games', name: 'werewolf_')]
final class WerewolfController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private WerewolfVoteService $voteService,
        private TeamMemberRepositoryInterface $members,
    ) {
    }

    /**
     * GET /games/{id}/me/role
     * Returns the caller's own role for the given game (villager|werewolf) if assigned.
     */
    #[Route('/{id}/me/role', name: 'me_role', methods: ['GET'])]
    public function myRole(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $game = $this->em->getRepository(Game::class)->find($id);
        if (!$game) {
            return $this->json(['error' => 'game_not_found'], 404);
        }

        // Only participants can query their role
        $membership = $this->members->findOneByGameAndUser($game, $user);
        if (!$membership) {
            return $this->json(['error' => 'not_a_participant'], 403);
        }

        $role = $this->em->getRepository(GameRole::class)->findOneBy(['game' => $game, 'user' => $user]);

        return $this->json([
            'gameId' => $id,
            'role' => $role?->getRole(), // null until assigned
        ]);
    }

    /**
     * POST /games/{id}/votes { suspectUserId }.
     */
    #[Route('/{id}/votes', name: 'vote', methods: ['POST'])]
    public function vote(string $id, Request $r): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $game = $this->em->getRepository(Game::class)->find($id);
        if (!$game) {
            return $this->json(['error' => 'game_not_found'], 404);
        }
        // Only participants can vote
        $membership = $this->members->findOneByGameAndUser($game, $user);
        if (!$membership) {
            return $this->json(['error' => 'not_a_participant'], 403);
        }

        if (!$game->isVoteOpen()) {
            return $this->json(['error' => 'vote_closed'], 409);
        }

        $payload = $r->toArray();
        $suspectId = (string) ($payload['suspectUserId'] ?? '');
        if ('' === $suspectId) {
            return $this->json(['error' => 'invalid_suspect'], 400);
        }
        $suspect = $this->em->getRepository(User::class)->find($suspectId);
        if (!$suspect) {
            return $this->json(['error' => 'suspect_not_found'], 404);
        }

        try {
            $vote = $this->voteService->castVote($game, $user, $suspect);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'vote_failed', 'message' => $e->getMessage()], 400);
        }

        return $this->json([
            'ok' => true,
            'voteId' => $vote->getId(),
            'voterId' => $user->getId(),
            'suspectId' => $suspect->getId(),
            'createdAt' => $vote->getCreatedAt()->getTimestamp() * 1000,
        ], 201);
    }

    /**
     * GET /games/{id}/votes - live tally.
     */
    #[Route('/{id}/votes', name: 'votes', methods: ['GET'])]
    public function votes(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $game = $this->em->getRepository(Game::class)->find($id);
        if (!$game) {
            return $this->json(['error' => 'game_not_found'], 404);
        }

        $counts = $this->voteService->getLiveResults($game);

        return $this->json([
            'gameId' => $id,
            'voteOpen' => $game->isVoteOpen(),
            'results' => $counts,
        ]);
    }

    /**
     * POST /games/{id}/votes/close - close voting (admin or game creator in future).
     */
    #[Route('/{id}/votes/close', name: 'votes_close', methods: ['POST'])]
    public function votesClose(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $game = $this->em->getRepository(Game::class)->find($id);
        if (!$game) {
            return $this->json(['error' => 'game_not_found'], 404);
        }

        // Only creator or admin can close vote
        $isCreator = $game->getCreatedBy()?->getId() === $user->getId();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        if (!$isCreator && !$isAdmin) {
            return $this->json(['error' => 'forbidden'], 403);
        }

        $this->voteService->closeVote($game);

        return $this->json([
            'ok' => true,
            'gameId' => $id,
            'voteOpen' => false,
        ]);
    }
}
