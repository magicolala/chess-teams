<?php

namespace App\Tests\Functional;

use App\Application\DTO\CreateGameInput;
use App\Application\DTO\StartGameInput;
use App\Application\UseCase\CreateGameHandler;
use App\Application\UseCase\StartGameHandler;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class GameTimeoutTest extends WebTestCase
{
    use _AuthTestTrait;

    public function testTickAppliesTimeoutAndSwitchesTurn(): void
    {
        $client = self::createClient();
        $c = self::getContainer();
        $em = $c->get('doctrine')->getManager();

        $uA = new User();
        $uA->setEmail('ta+'.bin2hex(random_bytes(3)).'@test.io');
        $uA->setPassword(password_hash('x', PASSWORD_BCRYPT));
        $uB = new User();
        $uB->setEmail('tb+'.bin2hex(random_bytes(3)).'@test.io');
        $uB->setPassword(password_hash('x', PASSWORD_BCRYPT));
        $em->persist($uA);
        $em->persist($uB);
        $em->flush();

        /** @var CreateGameHandler $create */
        $create = $c->get(CreateGameHandler::class);
        $out = $create(new CreateGameInput($uA->getId() ?? 'x', 5, 'private'), $uA);
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

        // démarre
        /** @var StartGameHandler $start */
        $start = $c->get(StartGameHandler::class);
        $start(new StartGameInput($game->getId(), $uA->getId() ?? ''), $uA);

        // force la deadline passée
        $game->setTurnDeadline((new \DateTimeImmutable())->modify('-2 seconds'));
        $em->flush();

        // tick
        $this->loginClient($client, $uA);
        $client->request('POST', '/games/'.$game->getId().'/tick');
        $this->assertResponseStatusCodeSame(201);

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertTrue($json['timedOutApplied']);
        self::assertSame(1, $json['ply']);
        self::assertSame('B', $json['turnTeam']);
    }

    public function testTickNoopIfNotExpired(): void
    {
        $client = self::createClient();
        $c = self::getContainer();
        $em = $c->get('doctrine')->getManager();

        $uA = new User();
        $uA->setEmail('tc+'.bin2hex(random_bytes(3)).'@test.io');
        $uA->setPassword(password_hash('x', PASSWORD_BCRYPT));
        $uB = new User();
        $uB->setEmail('td+'.bin2hex(random_bytes(3)).'@test.io');
        $uB->setPassword(password_hash('x', PASSWORD_BCRYPT));
        $em->persist($uA);
        $em->persist($uB);
        $em->flush();

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

        /** @var StartGameHandler $start */
        $start = $c->get(StartGameHandler::class);
        $start(new StartGameInput($game->getId(), $uA->getId() ?? ''), $uA);

        // deadline future (pas expirée)
        $this->loginClient($client, $uA);
        $client->request('POST', '/games/'.$game->getId().'/tick');
        $this->assertResponseStatusCodeSame(200);

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertFalse($json['timedOutApplied']);
        self::assertSame(0, $json['ply']);
        self::assertSame('A', $json['turnTeam']);
    }
}
