<?php

namespace App\Tests\Functional;

use App\Application\DTO\CreateGameInput;
use App\Application\UseCase\CreateGameHandler;
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
final class GameShowTest extends WebTestCase
{
    public function testShowGameReturnsState(): void
    {
        $client = self::createClient();
        $c = self::getContainer();
        $em = $c->get('doctrine')->getManager();

        // users
        $u1 = new User();
        $u1->setEmail('s1+'.bin2hex(random_bytes(3)).'@test.io');
        $u1->setPassword(password_hash('x', PASSWORD_BCRYPT));
        $u2 = new User();
        $u2->setEmail('s2+'.bin2hex(random_bytes(3)).'@test.io');
        $u2->setPassword(password_hash('x', PASSWORD_BCRYPT));
        $em->persist($u1);
        $em->persist($u2);
        $em->flush();

        // create game
        /** @var CreateGameHandler $create */
        $create = $c->get(CreateGameHandler::class);
        $out = $create(new CreateGameInput($u1->getId() ?? 'x', 60, 'private'), $u1);

        $game = $em->getRepository(\App\Entity\Game::class)->find($out->gameId);
        /** @var TeamRepositoryInterface $teams */
        $teams = $c->get(TeamRepositoryInterface::class);
        $teamA = $teams->findOneByGameAndName($game, Team::NAME_A);
        $teamB = $teams->findOneByGameAndName($game, Team::NAME_B);

        /** @var TeamMemberRepositoryInterface $members */
        $members = $c->get(TeamMemberRepositoryInterface::class);
        $members->add(new TeamMember($teamA, $u1, 0));
        $members->add(new TeamMember($teamB, $u2, 0));
        $em->flush();

        // GET /games/{id}
        $client->request('GET', '/games/'.$game->getId());
        $this->assertResponseIsSuccessful();

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertSame($game->getId(), $json['id']);
        self::assertSame('A', $json['turnTeam']); // par défaut
        self::assertArrayHasKey('teams', $json);
        self::assertArrayHasKey('A', $json['teams']);
        self::assertArrayHasKey('B', $json['teams']);
        self::assertGreaterThanOrEqual(0, $json['teams']['A']['currentIndex']);
    }
}
