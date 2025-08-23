<?php

namespace App\Controller;

use App\Application\DTO\CreateGameInput;
use App\Application\UseCase\CreateGameHandler;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Annotation\Route;
use App\Application\DTO\JoinByCodeInput;
use App\Application\UseCase\JoinByCodeHandler;

#[Route('/games', name: 'game_')]
final class GameController extends AbstractController
{
	public function __construct(
		private CreateGameHandler $createGame,
		private JoinByCodeHandler $joinByCode // <-- ajoute l’arg
	) {}

	#[Route('', name: 'create', methods: ['POST'])]
	public function create(Request $r): JsonResponse
	{
		$this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
		/** @var User $user */
		$user = $this->getUser();

		$payload = $r->toArray();
		$in = new CreateGameInput(
			creatorUserId: $user->getId(),
			turnDurationSec: isset($payload['turnDurationSec']) ? (int)$payload['turnDurationSec'] : 60,
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
		/** @var \App\Entity\User $user */
		$user = $this->getUser();

		$in = new JoinByCodeInput(inviteCode: $code, userId: $user->getId() ?? '');
		$out = ($this->joinByCode)($in, $user);

		return $this->json([
			'ok' => true,
			'team' => $out->teamName,
			'position' => $out->position,
		]);
	}
}
