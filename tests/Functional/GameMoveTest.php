<?php

namespace App\Tests\Functional;

use App\Application\DTO\CreateGameInput;
use App\Application\DTO\MarkPlayerReadyInput;
use App\Application\DTO\StartGameInput;
use App\Application\UseCase\CreateGameHandler;
use App\Application\UseCase\MarkPlayerReadyHandler;
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
final class GameMoveTest extends WebTestCase
{
    use _AuthTestTrait;

    public function testMakeMoveOk(): void
    {
        $client = self::createClient();
        $c = self::getContainer();
        $em = $c->get('doctrine')->getManager();

        // 2 users
        $uA = new User();
        $uA->setEmail('ma+'.bin2hex(random_bytes(3)).'@test.io');
        $uA->setPassword(password_hash('x', PASSWORD_BCRYPT));
        $uB = new User();
        $uB->setEmail('mb+'.bin2hex(random_bytes(3)).'@test.io');
        $uB->setPassword(password_hash('x', PASSWORD_BCRYPT));
        $em->persist($uA);
        $em->persist($uB);
        $em->flush();

        // create game by uA
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

        // Mark players as ready
        /** @var MarkPlayerReadyHandler $markReady */
        $markReady = $c->get(MarkPlayerReadyHandler::class);
        $markReady(new MarkPlayerReadyInput($game->getId(), $uA->getId()), $uA);
        $markReady(new MarkPlayerReadyInput($game->getId(), $uB->getId()), $uB);
        $em->flush();

        // start (as creator uA)
        /** @var StartGameHandler $start */
        $start = $c->get(StartGameHandler::class);
        $start($i = new StartGameInput($game->getId(), $uA->getId() ?? ''), $uA);

        // login as uA (team A must play first)
        $this->loginClient($client, $uA);

        // move
        $client->request(
            'POST',
            '/games/'.$game->getId().'/move',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['uci' => 'e2e4'])
        );
        $this->assertResponseStatusCodeSame(201);

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertSame(1, $json['ply']);
        self::assertSame('B', $json['turnTeam']);
        self::assertSame('rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq e3 0 1', $json['fen']);
    }
}
