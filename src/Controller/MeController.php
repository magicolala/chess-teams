<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class MeController extends AbstractController
{
    #[Route('/me', name: 'app_me', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        return $this->json([
            'id'          => method_exists($user, 'getId') ? $user->getId() : null,
            'email'       => $user->getUserIdentifier(),
            'displayName' => method_exists($user, 'getDisplayName') ? $user->getDisplayName() : null,
            'roles'       => $user->getRoles(),
            'createdAt'   => method_exists($user, 'getCreatedAt') ? $user->getCreatedAt()->format(DATE_ATOM) : null,
        ]);
    }
}
