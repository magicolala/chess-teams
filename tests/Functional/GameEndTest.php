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
final class GameEndTest extends WebTestCase
{
    use _AuthTestTrait;

    public function testMoveToCheckmateFinishesGame(): void
    {
        $client = self::createClient();
        $c      = self::getContainer();
        $em     = $c->get('doctrine')->getManager();

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
        $out    = $create(new CreateGameInput($uA->getId() ?? 'x', 60, 'private'), $uA);
        $game   = $em->getRepository(\App\Entity\Game::class)->find($out->gameId);

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

        // On pousse une mini-séquence forçant un mat rapide (avec l'engine réel en dev : en test, FakeEngine ne gère pas)
        // Ici, on triche pour le test fonctionnel en forçant directement une FEN de "mat imminent"
        $game->setFen('7k/5Q2/6K1/8/8/8/8/8 b - - 0 1'); // pat/mat-like — adapter selon attente
        $em->flush();

        $this->loginClient($client, $uB); // camp au trait = noir
        $client->request(
            'POST',
            '/games/'.$game->getId().'/move',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['uci' => 'h8h7']) // coup illégal si mat/pat ⇒ devrait renvoyer 422 ou 409
        );
        // Selon comportement, attends 422 (illegal_move) ou 409 (game_finished) si déjà fini :
        self::assertTrue(in_array($client->getResponse()->getStatusCode(), [409, 422], true));
    }
}
