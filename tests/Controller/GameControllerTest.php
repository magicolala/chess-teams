<?php

namespace App\Tests\Controller;

use App\Application\UseCase\ListMovesHandler;
use App\Application\UseCase\ShowGameHandler;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GameControllerTest extends WebTestCase
{
    private $client;
    private $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // Mock user for authentication
        $this->user = $this->createMock(User::class);
        $this->user->method('getId')->willReturn(123);
        $this->user->method('getUserIdentifier')->willReturn('test@example.com');
    }

    public function testCreateGameRequiresAuthentication(): void
    {
        $this->client->request('POST', '/games', [], [], [], json_encode([
            'turnDurationSec' => 60,
            'visibility' => 'private',
        ]));

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testJoinGameRequiresAuthentication(): void
    {
        $this->client->request('POST', '/games/join/TEST123');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testStartGameRequiresAuthentication(): void
    {
        $this->client->request('POST', '/games/game-id/start');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShowGameDoesNotRequireAuthentication(): void
    {
        // Mock the ShowGameHandler response
        $container = $this->getContainer();
        $showGameHandler = $this->createMock(ShowGameHandler::class);
        $showGameHandler->method('__invoke')->willReturn(
            new \App\Application\DTO\ShowGameOutput(
                id: 'game-id',
                status: 'waiting',
                fen: 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1',
                ply: 0,
                turnTeam: 'A',
                turnDeadlineTs: null,
                teamA: [],
                teamB: []
            )
        );

        $container->set(ShowGameHandler::class, $showGameHandler);

        $this->client->request('GET', '/games/test-game-id');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('game-id', $response['id']);
        $this->assertEquals('waiting', $response['status']);
    }

    public function testMoveRequiresAuthentication(): void
    {
        $this->client->request('POST', '/games/game-id/move', [], [], [], json_encode([
            'uci' => 'e2e4',
        ]));

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testTickRequiresAuthentication(): void
    {
        $this->client->request('POST', '/games/game-id/tick');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testMovesEndpointPublic(): void
    {
        // Mock the ListMovesHandler response
        $container = $this->getContainer();
        $listMovesHandler = $this->createMock(ListMovesHandler::class);
        $listMovesHandler->method('__invoke')->willReturn(
            new \App\Application\DTO\ListMovesOutput(
                gameId: 'game-id',
                moves: []
            )
        );

        $container->set(ListMovesHandler::class, $listMovesHandler);

        $this->client->request('GET', '/games/test-game-id/moves');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('game-id', $response['gameId']);
        $this->assertIsArray($response['moves']);
    }

    public function testMarkReadyRequiresAuthentication(): void
    {
        $this->client->request('POST', '/games/game-id/ready', [], [], [], json_encode([
            'ready' => true,
        ]));

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testEnableFastModeRequiresAuthentication(): void
    {
        $this->client->request('POST', '/games/game-id/enable-fast-mode');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testClaimVictoryRequiresAuthentication(): void
    {
        $this->client->request('POST', '/games/game-id/claim-victory');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
