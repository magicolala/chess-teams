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

final class GameStartTest extends WebTestCase
{
    use _AuthTestTrait;

    public function testStartGameOk(): void
    {
        $client = static::createClient();
        $c = static::getContainer();
        $em = $c->get('doctrine')->getManager();

        // users
        $creator = new User();
        $creator->setEmail('host+'.bin2hex(random_bytes(3)).'@test.io');
        $creator->setPassword(password_hash('x', PASSWORD_BCRYPT));
        $p2 = new User();
        $p2->setEmail('p2+'.bin2hex(random_bytes(3)).'@test.io');
        $p2->setPassword(password_hash('x', PASSWORD_BCRYPT));
        $em->persist($creator);
        $em->persist($p2);
        $em->flush();

        // create game (use case direct)
        /** @var CreateGameHandler $create */
        $create = $c->get(CreateGameHandler::class);
        $out = $create(new CreateGameInput($creator->getId() ?? 'x', 60, 'private'), $creator);

        // fetch game+teams
        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $gameRepo = $em->getRepository(Game::class);
        /** @var Game $game */
        $game = $gameRepo->find($out->gameId);

        /** @var TeamRepositoryInterface $teams */
        $teams = $c->get(TeamRepositoryInterface::class);
        $teamA = $teams->findOneByGameAndName($game, Team::NAME_A);
        $teamB = $teams->findOneByGameAndName($game, Team::NAME_B);

        // add one member in each team
        /** @var TeamMemberRepositoryInterface $members */
        $members = $c->get(TeamMemberRepositoryInterface::class);
        $members->add(new TeamMember($teamA, $creator, 0));
        $members->add(new TeamMember($teamB, $p2, 0));
        $em->flush();

        // login as creator
        $this->loginClient($client, $creator);

        // start
        $client->request('POST', '/games/'.$game->getId().'/start');
        $this->assertResponseIsSuccessful();

        $json = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('live', $json['status']);
        $this->assertSame('A', $json['turnTeam']);
        $this->assertArrayHasKey('turnDeadline', $json);
        $this->assertGreaterThan(time() * 1000, $json['turnDeadline'] - 1000);
    }

    public function testStartGameRequiresOneMemberEachTeam(): void
    {
        $client = static::createClient();
        $c = static::getContainer();
        $em = $c->get('doctrine')->getManager();

        $creator = new User();
        $creator->setEmail('host+'.bin2hex(random_bytes(3)).'@test.io');
        $creator->setPassword(password_hash('x', PASSWORD_BCRYPT));
        $em->persist($creator);
        $em->flush();

        /** @var CreateGameHandler $create */
        $create = $c->get(CreateGameHandler::class);
        $out = $create(new CreateGameInput($creator->getId() ?? 'x', 60, 'private'), $creator);

        $game = $em->getRepository(Game::class)->find($out->gameId);

        // Ajoute joueur seulement dans A
        /** @var TeamRepositoryInterface $teams */
        $teams = $c->get(TeamRepositoryInterface::class);
        $teamA = $teams->findOneByGameAndName($game, Team::NAME_A);
        $em->persist(new TeamMember($teamA, $creator, 0));
        $em->flush();

        $this->loginClient($client, $creator);

        $client->request('POST', '/games/'.$game->getId().'/start');
        $this->assertResponseStatusCodeSame(409); // conflict
    }
}
