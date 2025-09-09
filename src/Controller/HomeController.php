<?php

namespace App\Controller;

use App\Domain\Repository\GameRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct(private GameRepositoryInterface $games)
    {
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $publicGames = $this->games->findPublicGames(10);

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'publicGames' => $publicGames,
        ]);
    }
}
