<?php

namespace App\Tests\Functional\Werewolf;

use App\Application\DTO\CreateGameInput;
use App\Application\UseCase\CreateGameHandler;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\GameRole;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
final class WerewolfControllerTest extends WebTestCase
{
    use \App\Tests\Functional\_AuthTestTrait;

    public function testMyRoleParticipantOnly(): void
    {
        $client = self::createClient();
        $c = self::getContainer();
        $em = $c->get('doctrine')->getManager();

        // users
        $creator = (new User())->setEmail('w-host@t.io')->setPassword('x');
        $p1 = (new User())->setEmail('w-p1@t.io')->setPassword('x');
        $outsider = (new User())->setEmail('w-out@t.io')->setPassword('x');
        $em->persist($creator); $em->persist($p1); $em->persist($outsider); $em->flush();

        /** @var CreateGameHandler $create */
        $create = $c->get(CreateGameHandler::class);
        $out = $create(new CreateGameInput($creator->getId() ?? 'x', 60, 'private', 'werewolf', false), $creator);

        /** @var Game $game */
        $game = $em->getRepository(Game::class)->find($out->gameId);
        /** @var TeamRepositoryInterface $teams */
        $teams = $c->get(TeamRepositoryInterface::class);
        $teamA = $teams->findOneByGameAndName($game, Team::NAME_A);
        $teamB = $teams->findOneByGameAndName($game, Team::NAME_B);

        /** @var TeamMemberRepositoryInterface $members */
        $members = $c->get(TeamMemberRepositoryInterface::class);
        $members->add(new TeamMember($teamA, $p1, 0));
        $em->flush();

        // Assign role for p1
        $em->persist(new GameRole($game, $p1, Team::NAME_A, 'villager'));
        $em->flush();

        // outsider cannot query role
        $this->loginClient($client, $outsider);
        $client->request('GET', '/games/'.$game->getId().'/me/role');
        self::assertResponseStatusCodeSame(403);

        // participant can query role
        $this->loginClient($client, $p1);
        $client->request('GET', '/games/'.$game->getId().'/me/role');
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('villager', $data['role']);
    }

    public function testVoteAndTallyAndClosePermissions(): void
    {
        $client = self::createClient();
        $c = self::getContainer();
        $em = $c->get('doctrine')->getManager();

        $creator = (new User())->setEmail('w2-host@t.io')->setPassword('x');
        $a1 = (new User())->setEmail('w2-a1@t.io')->setPassword('x');
        $b1 = (new User())->setEmail('w2-b1@t.io')->setPassword('x');
        $em->persist($creator); $em->persist($a1); $em->persist($b1); $em->flush();

        /** @var CreateGameHandler $create */
        $create = $c->get(CreateGameHandler::class);
        $out = $create(new CreateGameInput($creator->getId() ?? 'x', 60, 'private', 'werewolf', false), $creator);
        /** @var Game $game */
        $game = $em->getRepository(Game::class)->find($out->gameId);

        /** @var TeamRepositoryInterface $teams */
        $teams = $c->get(TeamRepositoryInterface::class);
        $teamA = $teams->findOneByGameAndName($game, Team::NAME_A);
        $teamB = $teams->findOneByGameAndName($game, Team::NAME_B);
        /** @var TeamMemberRepositoryInterface $members */
        $members = $c->get(TeamMemberRepositoryInterface::class);
        $members->add(new TeamMember($teamA, $a1, 0));
        $members->add(new TeamMember($teamB, $b1, 0));
        $em->flush();

        // roles
        $em->persist(new GameRole($game, $a1, Team::NAME_A, 'villager'));
        $em->persist(new GameRole($game, $b1, Team::NAME_B, 'werewolf'));
        $em->flush();

        // open vote
        $game->setVoteOpen(true);
        $game->setVoteStartedAt(new \DateTimeImmutable());
        $em->flush();

        // a1 casts vote on b1
        $this->loginClient($client, $a1);
        $client->request('POST', '/games/'.$game->getId().'/votes', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['suspectUserId' => $b1->getId()]));
        self::assertResponseStatusCodeSame(201);

        // tally visible
        $client->request('GET', '/games/'.$game->getId().'/votes');
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertTrue($data['voteOpen']);
        self::assertSame(1, $data['results'][$b1->getId()] ?? 0);

        // non-creator cannot close
        $client->request('POST', '/games/'.$game->getId().'/votes/close');
        self::assertResponseStatusCodeSame(403);

        // creator can close
        $this->loginClient($client, $creator);
        $client->request('POST', '/games/'.$game->getId().'/votes/close');
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertFalse($data['voteOpen']);

        // tally after close still accessible and unchanged
        $client->request('GET', '/games/'.$game->getId().'/votes');
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertFalse($data['voteOpen']);
        self::assertSame(1, $data['results'][$b1->getId()] ?? 0);
    }
}
