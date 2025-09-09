<?php

namespace App\Tests\Controller;

use App\Application\UseCase\ListMovesHandler;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GameMovesFilteringTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testMovesEndpointFiltersInvalidEntriesAndNormalizesTeam(): void
    {
        $container = $this->getContainer();

        // Moves payload returned by the handler (before controller filtering/normalization)
        $rawMoves = [
            // Valid SAN
            ['ply' => 1, 'san' => 'e4', 'uci' => 'e2e4', 'team' => ['name' => 'A']],
            // Valid UCI only
            ['ply' => 2, 'san' => null, 'uci' => 'e7e5', 'team' => ['name' => 'B']],
            // Timeout pass
            ['ply' => 3, 'san' => null, 'uci' => null, 'type' => 'timeout-pass', 'team' => ['name' => 'A']],
            // Invalid: neither SAN nor UCI and not timeout-pass
            ['ply' => 4, 'san' => null, 'uci' => null, 'team' => ['name' => 'B']],
        ];

        $listMovesHandler = $this->createMock(ListMovesHandler::class);
        $listMovesHandler->method('__invoke')->willReturn(
            new \App\Application\DTO\ListMovesOutput(
                gameId: 'game-id',
                moves: $rawMoves
            )
        );
        $container->set(ListMovesHandler::class, $listMovesHandler);

        $this->client->request('GET', '/games/test-game-id/moves');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('game-id', $response['gameId']);
        $this->assertIsArray($response['moves']);

        // The invalid move (ply 4) should be filtered out
        $plys = array_column($response['moves'], 'ply');
        $this->assertNotContains(4, $plys, 'Invalid move without SAN/uci and not timeout should be filtered');

        // Teams should be normalized to simple strings (e.g., 'A' / 'B')
        foreach ($response['moves'] as $m) {
            $this->assertIsString($m['team'] ?? '', 'Team should be normalized to string');
            $this->assertContains($m['team'], ['A', 'B'], 'Team should be A or B');
        }

        // Timeout move should remain present
        $this->assertContains(3, $plys, 'Timeout-pass move should be kept');
    }
}
