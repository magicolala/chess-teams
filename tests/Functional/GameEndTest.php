<?php

namespace App\Tests\Functional;

use App\Application\DTO\CreateGameInput;
use App\Application\DTO\StartGameInput;
use App\Application\UseCase\CreateGameHandler;
use App\Application\UseCase\StartGameHandler;
use App\Application\UseCase\MarkPlayerReadyHandler;
use App\Application\DTO\MarkPlayerReadyInput;
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
final class GameEndTest extends WebTestCase
{
    use _AuthTestTrait;

    public function testMoveToCheckmateFinishesGame(): void
    {
        $client = self::createClient();
        $c = self::getContainer();
        $em = $c->get('doctrine')->getManager();

        $uA = new User();
        $uA->setEmail('ge+'.bin2hex(random_bytes(3)).'@test.io');
        $uA->setPassword(password_hash('x', PASSWORD_BCRYPT));
        $uB = new User();
        $uB->setEmail('gf+'.bin2hex(random_bytes(3)).'@test.io');
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

        /** @var MarkPlayerReadyHandler $markReady */
        $markReady = $c->get(MarkPlayerReadyHandler::class);
        $markReady(new MarkPlayerReadyInput($game->getId(), true), $uA);
        $markReady(new MarkPlayerReadyInput($game->getId(), true), $uB);
        $em->flush();

        /** @var StartGameHandler $start */
        $start = $c->get(StartGameHandler::class);
        $start(new StartGameInput($game->getId(), $uA->getId() ?? ''), $uA);

        // Ici on simule la fin de partie pour tester la protection HTTP (FakeEngine ne calcule pas la fin).
        $game->setStatus('finished');
        $game->setResult('A#');
        $em->flush();
        $this->loginClient($client, $uB);
        $client->request(
            'POST',
            '/games/'.$game->getId().'/move',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['uci' => 'e7e5'])
        );
        self::assertSame(409, $client->getResponse()->getStatusCode());
    }
}
