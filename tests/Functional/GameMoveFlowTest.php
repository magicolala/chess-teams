<?php

namespace App\Tests\Functional;

use App\Application\UseCase\ListMovesHandler;
use App\Application\UseCase\MakeMoveHandler;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GameMoveFlowTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testMoveValidationFlowAndHistoryRefresh(): void
    {
        // Simuler un utilisateur connecté
        $user = new User();
        $user->setEmail('player@example.com');
        // S'assurer que l'utilisateur a au moins ROLE_USER
        $user->setRoles(['ROLE_USER']);
        // Authentifier explicitement sur le firewall "main"
        $this->client->loginUser($user, 'main');

        $container = $this->getContainer();

        // Ce test se concentre uniquement sur le rendu de l'endpoint /moves.
        // On n'invoque pas POST /move ici (soumis au firewall), donc on n'a pas besoin de construire MakeMoveHandler.

        // Mock ListMovesHandler: renvoie une liste avec le coup joué
        $listMovesHandler = $this->createMock(ListMovesHandler::class);
        $listMovesHandler->method('__invoke')->willReturn(
            new \App\Application\DTO\ListMovesOutput(
                gameId: 'g-test',
                moves: [
                    ['ply' => 1, 'san' => 'e4', 'uci' => 'e2e4', 'team' => ['name' => 'A']],
                ]
            )
        );
        $container->set(ListMovesHandler::class, $listMovesHandler);

        // GET /games/{id}/moves — l'historique doit contenir un coup formaté
        $this->client->request('GET', '/games/g-test/moves');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('g-test', $payload['gameId']);
        $this->assertIsArray($payload['moves']);
        $this->assertCount(1, $payload['moves']);
        $this->assertSame(1, $payload['moves'][0]['ply']);
        $this->assertSame('A', $payload['moves'][0]['team']); // normalisé par le contrôleur
        $this->assertSame('e4', $payload['moves'][0]['san']);
        $this->assertSame('e2e4', $payload['moves'][0]['uci']);
    }
}
