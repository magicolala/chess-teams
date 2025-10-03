<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test', name: 'test_')]
final class TestAutoRefreshController extends AbstractController
{
    #[Route('/auto-refresh', name: 'auto_refresh', methods: ['GET'])]
    public function autoRefresh(): Response
    {
        return $this->render('test/auto_refresh_demo.html.twig');
    }

    #[Route('/auto-refresh-content', name: 'auto_refresh_content', methods: ['GET'])]
    public function autoRefreshContent(): Response
    {
        // Simuler du contenu qui change
        $randomData = [
            'timestamp' => date('H:i:s'),
            'random_number' => rand(1, 1000),
            'last_update' => date('d/m/Y H:i:s'),
            'counter' => time() % 100, // Un compteur qui change
        ];

        // Si c'est une requête AJAX, retourner juste le contenu partiel
        if ($this->isXmlHttpRequest()) {
            return $this->render('test/auto_refresh_content.html.twig', $randomData);
        }

        // Sinon, retourner la page complète
        return $this->render('test/auto_refresh_demo.html.twig', $randomData);
    }

    private function isXmlHttpRequest(): bool
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        return $request && 'XMLHttpRequest' === $request->headers->get('X-Requested-With');
    }
}
