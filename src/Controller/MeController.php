<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class MeController extends AbstractController
{
    #[Route('/me', name: 'app_me', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('Authenticated user must be an application user instance.');
        }

        $createdAt = $user->getCreatedAt();
        $createdAtIso = $createdAt instanceof \DateTimeInterface ? $createdAt->format(DATE_ATOM) : null;

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getUserIdentifier(),
            'displayName' => $user->getDisplayName(),
            'roles' => $user->getRoles(),
            'createdAt' => $createdAtIso,
        ]);
    }
}
