<?php

namespace App\Tests\Functional;

use App\Application\DTO\CreateGameInput;
use App\Application\UseCase\CreateGameHandler;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class GameReadyTest extends WebTestCase
{
    use _AuthTestTrait;

    public function testMarkPlayerReady(): void
    {
        $client = self::createClient();
        $c = self::getContainer();
        $em = $c->get('doctrine')->getManager();

        // Create users
        $creator = new User();
        $creator->setEmail('host+'.bin2hex(random_bytes(3)).'@test.io');
        $creator->setPassword(password_hash('x', PASSWORD_BCRYPT));
        $p2 = new User();
        $p2->setEmail('p2+'.bin2hex(random_bytes(3)).'@test.io');
        $p2->setPassword(password_hash('x', PASSWORD_BCRYPT));
        $em->persist($creator);
        $em->persist($p2);
        $em->flush();

        // Create game
        /** @var CreateGameHandler $create */
        $create = $c->get(CreateGameHandler::class);
        $out = $create(new CreateGameInput($creator->getId() ?? 'x', 60, 'private'), $creator);

        // Fetch game+teams
        $gameRepo = $em->getRepository(Game::class);
        /** @var Game $game */
        $game = $gameRepo->find($out->gameId);

        /** @var TeamRepositoryInterface $teams */
        $teams = $c->get(TeamRepositoryInterface::class);
        $teamA = $teams->findOneByGameAndName($game, Team::NAME_A);
        $teamB = $teams->findOneByGameAndName($game, Team::NAME_B);

        // Add members to teams
        /** @var TeamMemberRepositoryInterface $members */
        $members = $c->get(TeamMemberRepositoryInterface::class);
        $memberA = new TeamMember($teamA, $creator, 0);
        $memberB = new TeamMember($teamB, $p2, 0);
        $members->add($memberA);
        $members->add($memberB);
        $em->flush();

        // Login as creator
        $this->loginClient($client, $creator);

        // Test marking as ready
        $client->request('POST', '/games/'.$game->getId().'/ready', [], [], ['CONTENT_TYPE' => 'application/json'], '{"ready": true}');
        $this->assertResponseIsSuccessful();

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertSame($game->getId(), $json['gameId']);
        self::assertSame((string) $creator->getId(), $json['userId']);
        self::assertTrue($json['ready']);
        self::assertEquals(1, $json['readyPlayersCount']);
        self::assertEquals(2, $json['totalPlayersCount']);
        self::assertFalse($json['allPlayersReady']);

        // Mark second player as ready
        $this->loginClient($client, $p2);
        $client->request('POST', '/games/'.$game->getId().'/ready', [], [], ['CONTENT_TYPE' => 'application/json'], '{"ready": true}');
        $this->assertResponseIsSuccessful();

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertSame($game->getId(), $json['gameId']);
        self::assertSame((string) $p2->getId(), $json['userId']);
        self::assertTrue($json['ready']);
        self::assertEquals(2, $json['readyPlayersCount']);
        self::assertEquals(2, $json['totalPlayersCount']);
        self::assertTrue($json['allPlayersReady']);
    }

    public function testMarkPlayerNotReady(): void
    {
        $client = self::createClient();
        $c = self::getContainer();
        $em = $c->get('doctrine')->getManager();

        // Create user
        $creator = new User();
        $creator->setEmail('host+'.bin2hex(random_bytes(3)).'@test.io');
        $creator->setPassword(password_hash('x', PASSWORD_BCRYPT));
        $em->persist($creator);
        $em->flush();

        // Create game
        /** @var CreateGameHandler $create */
        $create = $c->get(CreateGameHandler::class);
        $out = $create(new CreateGameInput($creator->getId() ?? 'x', 60, 'private'), $creator);

        $gameRepo = $em->getRepository(Game::class);
        /** @var Game $game */
        $game = $gameRepo->find($out->gameId);

        /** @var TeamRepositoryInterface $teams */
        $teams = $c->get(TeamRepositoryInterface::class);
        $teamA = $teams->findOneByGameAndName($game, Team::NAME_A);

        /** @var TeamMemberRepositoryInterface $members */
        $members = $c->get(TeamMemberRepositoryInterface::class);
        $memberA = new TeamMember($teamA, $creator, 0);
        $memberA->setReadyToStart(true); // Start as ready
        $members->add($memberA);
        $em->flush();

        // Login as creator
        $this->loginClient($client, $creator);

        // Test marking as not ready
        $client->request('POST', '/games/'.$game->getId().'/ready', [], [], ['CONTENT_TYPE' => 'application/json'], '{"ready": false}');
        $this->assertResponseIsSuccessful();

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertSame($game->getId(), $json['gameId']);
        self::assertSame((string) $creator->getId(), $json['userId']);
        self::assertFalse($json['ready']);
        self::assertEquals(0, $json['readyPlayersCount']);
        self::assertEquals(1, $json['totalPlayersCount']);
        self::assertFalse($json['allPlayersReady']);
    }

    public function testStartGameRequiresAllPlayersReady(): void
    {
        $client = self::createClient();
        $c = self::getContainer();
        $em = $c->get('doctrine')->getManager();

        // Create users
        $creator = new User();
        $creator->setEmail('host+'.bin2hex(random_bytes(3)).'@test.io');
        $creator->setPassword(password_hash('x', PASSWORD_BCRYPT));
        $p2 = new User();
        $p2->setEmail('p2+'.bin2hex(random_bytes(3)).'@test.io');
        $p2->setPassword(password_hash('x', PASSWORD_BCRYPT));
        $em->persist($creator);
        $em->persist($p2);
        $em->flush();

        // Create game
        /** @var CreateGameHandler $create */
        $create = $c->get(CreateGameHandler::class);
        $out = $create(new CreateGameInput($creator->getId() ?? 'x', 60, 'private'), $creator);

        $gameRepo = $em->getRepository(Game::class);
        /** @var Game $game */
        $game = $gameRepo->find($out->gameId);

        /** @var TeamRepositoryInterface $teams */
        $teams = $c->get(TeamRepositoryInterface::class);
        $teamA = $teams->findOneByGameAndName($game, Team::NAME_A);
        $teamB = $teams->findOneByGameAndName($game, Team::NAME_B);

        // Add members to teams but don't mark as ready
        /** @var TeamMemberRepositoryInterface $members */
        $members = $c->get(TeamMemberRepositoryInterface::class);
        $memberA = new TeamMember($teamA, $creator, 0);
        $memberB = new TeamMember($teamB, $p2, 0);
        $members->add($memberA);
        $members->add($memberB);
        $em->flush();

        // Login as creator
        $this->loginClient($client, $creator);

        // Try to start game without all players ready
        $client->request('POST', '/games/'.$game->getId().'/start');
        $this->assertResponseStatusCodeSame(409); // Conflict - not all players ready

        // Mark both players as ready
        $memberA->setReadyToStart(true);
        $memberB->setReadyToStart(true);
        $em->flush();

        // Now start should work
        $client->request('POST', '/games/'.$game->getId().'/start');
        $this->assertResponseIsSuccessful();

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('live', $json['status']);
    }
}
