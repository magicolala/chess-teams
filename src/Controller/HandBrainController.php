<?php

namespace App\Controller;

use App\Application\Service\Game\HandBrain\DTO\HandBrainState;
use App\Application\Service\Game\HandBrain\HandBrainHintService;
use App\Application\Service\Game\HandBrain\HandBrainModeService;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/games/{id}/hand-brain', name: 'hand_brain_')]
final class HandBrainController extends AbstractController
{
    public function __construct(
        private readonly HandBrainModeService $modeService,
        private readonly HandBrainHintService $hintService,
        private readonly HubInterface $mercureHub,
    ) {
    }

    #[Route('/enable', name: 'enable', methods: ['POST'])]
    public function enable(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $state = $this->modeService->enable($id, $user);
        $payload = $this->serializeState($state);

        $response = $this->json($payload);

        $this->publishMercure($state->gameId, [
            'type' => 'hand_brain_roles_assigned',
            ...$payload,
        ]);

        return $response;
    }

    #[Route('/hint', name: 'hint', methods: ['POST'])]
    public function hint(string $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $payload = $request->toArray();
        $piece = (string) ($payload['piece'] ?? '');

        $state = $this->hintService->setHint($id, $piece, $user);
        $statePayload = $this->serializeState($state);

        $response = $this->json($statePayload);

        $this->publishMercure($state->gameId, [
            'type' => 'hand_brain_hint_set',
            ...$statePayload,
        ]);

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeState(HandBrainState $state): array
    {
        return [
            'gameId' => $state->gameId,
            'currentRole' => $state->currentRole,
            'pieceHint' => $state->pieceHint,
            'brainMemberId' => $state->brainMemberId,
            'handMemberId' => $state->handMemberId,
        ];
    }

    private function publishMercure(string $gameId, array $data): void
    {
        try {
            $update = new Update(
                topics: sprintf('/games/%s', $gameId),
                data: json_encode($data, JSON_THROW_ON_ERROR)
            );
            $this->mercureHub->publish($update);
        } catch (\Throwable) {
            // Mercure errors should not break the request flow.
        }
    }
}
