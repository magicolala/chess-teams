<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use App\Application\DTO\{CreateGameInput, StartGameInput};
use App\Application\UseCase\{CreateGameHandler, StartGameHandler};
use App\Domain\Repository\{TeamRepositoryInterface, TeamMemberRepositoryInterface};
use App\Entity\{Team, TeamMember};

final class GameMovesListTest extends WebTestCase
{
    use _AuthTestTrait;

    public function test_get_moves_returns_array(): void
    {
        $client = static::createClient();
        $c = static::getContainer();
        $em = $c->get('doctrine')->getManager();

        // users
        $uA = new User();
        $uA->setEmail('lm+' . bin2hex(random_bytes(3)) . '@test.io');
        $uA->setPassword(password_hash('x', PASSWORD_BCRYPT));
        $uB = new User();
        $uB->setEmail('ln+' . bin2hex(random_bytes(3)) . '@test.io');
        $uB->setPassword(password_hash('x', PASSWORD_BCRYPT));
        $em->persist($uA);
        $em->persist($uB);
        $em->flush();

        // create game
        /** @var CreateGameHandler $create */
        $create = $c->get(CreateGameHandler::class);
        $out = $create(new CreateGameInput($uA->getId() ?? 'x', 60, 'private'), $uA);
        $game = $em->getRepository(\App\Entity\Game::class)->find($out->gameId);

        /** @var TeamRepositoryInterface $teams */
        $teams = $c->get(TeamRepositoryInterface::class);
        $teamA = $teams->findOneByGameAndName($game, Team::NAME_A);
        $teamB = $teams->findOneByGameAndName($game, Team::NAME_B);

        /** @var TeamMemberRepositoryInterface $members */
        $members = $c->get(TeamMemberRepositoryInterface::class);
        $members->add(new TeamMember($teamA, $uA, 0));
        $members->add(new TeamMember($teamB, $uB, 0));
        $em->flush();

        // start
        /** @var StartGameHandler $start */
        $start = $c->get(StartGameHandler::class);
        $start(new StartGameInput($game->getId(), $uA->getId() ?? ''), $uA);

        // A joue un coup via HTTP
        $this->loginClient($client, $uA);
        $client->request(
            'POST',
            '/games/' . $game->getId() . '/move',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['uci' => 'e2e4'])
        );
        $this->assertResponseStatusCodeSame(201);

        // GET /moves
        $client->request('GET', '/games/' . $game->getId() . '/moves');
        $this->assertResponseIsSuccessful();

        $json = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('moves', $json);
        $this->assertIsArray($json['moves']);
        $this->assertGreaterThanOrEqual(1, count($json['moves']));
        $this->assertSame('e2e4', $json['moves'][0]['uci']);
    }
}
