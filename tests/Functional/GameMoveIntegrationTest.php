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
 * Tests d'intégration pour s'assurer que les coups normaux fonctionnent
 * sans problème de promotion sur le serveur.
 *
 * @internal
 *
 * @coversNothing
 */
final class GameMoveIntegrationTest extends WebTestCase
{
    use _AuthTestTrait;

    public function testNormalPawnMoveWorks(): void
    {
        $client = self::createClient();
        $game = $this->createAndStartGame($client);

        // Login as player A (white pieces)
        $this->loginClient($client, $game['userA']);

        // Test e2-e4 (normal pawn move)
        $client->request(
            'POST',
            '/games/'.$game['gameId'].'/move',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['uci' => 'e2e4'])
        );

        self::assertResponseStatusCodeSame(201);

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertSame(1, $json['ply']);
        self::assertSame('B', $json['turnTeam']);
        // Vérifier que c'est bien un coup de pion normal (la FEN devrait montrer le pion sur e4)
        self::assertStringContainsString('4P3', $json['fen']);
    }

    public function testNormalKnightMoveWorks(): void
    {
        $client = self::createClient();
        $game = $this->createAndStartGame($client);

        // Login as player A (white pieces)
        $this->loginClient($client, $game['userA']);

        // Test g1-f3 (knight move)
        $client->request(
            'POST',
            '/games/'.$game['gameId'].'/move',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['uci' => 'g1f3'])
        );

        self::assertResponseStatusCodeSame(201);

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertSame(1, $json['ply']);
        self::assertSame('B', $json['turnTeam']);
        // La FEN devrait refléter le mouvement du cavalier
        self::assertIsString($json['fen']);
        self::assertNotEmpty($json['fen']);
    }

    public function testMultipleNormalMovesInSequence(): void
    {
        $client = self::createClient();
        $game = $this->createAndStartGame($client);

        // Séquence de coups normaux : e2-e4, e7-e5, Ng1-f3, Nb8-c6
        $moves = [
            ['uci' => 'e2e4', 'player' => $game['userA'], 'expectedTurn' => 'B'],
            ['uci' => 'e7e5', 'player' => $game['userB'], 'expectedTurn' => 'A'],
            ['uci' => 'g1f3', 'player' => $game['userA'], 'expectedTurn' => 'B'],
            ['uci' => 'b8c6', 'player' => $game['userB'], 'expectedTurn' => 'A'],
        ];

        foreach ($moves as $index => $move) {
            $this->loginClient($client, $move['player']);

            $client->request(
                'POST',
                '/games/'.$game['gameId'].'/move',
                server: ['CONTENT_TYPE' => 'application/json'],
                content: json_encode(['uci' => $move['uci']])
            );

            self::assertResponseStatusCodeSame(
                201,
                "Coup {$move['uci']} devrait être accepté"
            );

            $json = json_decode($client->getResponse()->getContent(), true);
            self::assertSame($index + 1, $json['ply']);
            self::assertSame($move['expectedTurn'], $json['turnTeam']);
        }
    }

    public function testInvalidUciReturns422(): void
    {
        $client = self::createClient();
        $game = $this->createAndStartGame($client);

        $this->loginClient($client, $game['userA']);

        // Tester un coup UCI invalide
        $client->request(
            'POST',
            '/games/'.$game['gameId'].'/move',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['uci' => 'invalid_move'])
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testIllegalMoveReturns422(): void
    {
        $client = self::createClient();
        $game = $this->createAndStartGame($client);

        $this->loginClient($client, $game['userA']);

        // Tester un coup légalement impossible (e2 vers e2)
        $client->request(
            'POST',
            '/games/'.$game['gameId'].'/move',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['uci' => 'e2e2'])
        );

        self::assertResponseStatusCodeSame(422);
    }

    /**
     * Crée une partie et la démarre avec deux joueurs.
     */
    private function createAndStartGame($client): array
    {
        $c = self::getContainer();
        $em = $c->get('doctrine')->getManager();

        // Créer deux utilisateurs
        $userA = new User();
        $userA->setEmail('testa+'.bin2hex(random_bytes(3)).'@test.io');
        $userA->setPassword(password_hash('x', PASSWORD_BCRYPT));

        $userB = new User();
        $userB->setEmail('testb+'.bin2hex(random_bytes(3)).'@test.io');
        $userB->setPassword(password_hash('x', PASSWORD_BCRYPT));

        $em->persist($userA);
        $em->persist($userB);
        $em->flush();

        // Créer la partie
        /** @var CreateGameHandler $create */
        $create = $c->get(CreateGameHandler::class);
        $out = $create(new CreateGameInput($userA->getId() ?? 'x', 60, 'private'), $userA);
        $game = $em->getRepository(\App\Entity\Game::class)->find($out->gameId);

        // Ajouter les joueurs aux équipes
        /** @var TeamRepositoryInterface $teams */
        $teams = $c->get(TeamRepositoryInterface::class);
        $teamA = $teams->findOneByGameAndName($game, Team::NAME_A);
        $teamB = $teams->findOneByGameAndName($game, Team::NAME_B);

        /** @var TeamMemberRepositoryInterface $members */
        $members = $c->get(TeamMemberRepositoryInterface::class);
        $members->add(new TeamMember($teamA, $userA, 0));
        $members->add(new TeamMember($teamB, $userB, 0));
        $em->flush();

        // Mark players as ready
        /** @var MarkPlayerReadyHandler $markReady */
        $markReady = $c->get(MarkPlayerReadyHandler::class);
        $markReady(new MarkPlayerReadyInput($game->getId(), $userA->getId()), $userA);
        $markReady(new MarkPlayerReadyInput($game->getId(), $userB->getId()), $userB);
        $em->flush();

        // Démarrer la partie
        /** @var StartGameHandler $start */
        $start = $c->get(StartGameHandler::class);
        $start(new StartGameInput($game->getId(), $userA->getId() ?? ''), $userA);

        return [
            'gameId' => $game->getId(),
            'userA' => $userA,  // Équipe A (blancs)
            'userB' => $userB,  // Équipe B (noirs)
        ];
    }
}
