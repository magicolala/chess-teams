<?php

namespace App\Tests\Functional;

use App\Application\DTO\CreateGameInput;
use App\Application\DTO\StartGameInput;
use App\Application\UseCase\CreateGameHandler;
use App\Application\UseCase\StartGameHandler;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class HandBrainControllerTest extends WebTestCase
{
    use _AuthTestTrait;

    public function testEnableHandBrainModeAssignsRoles(): void
    {
        [$client, $em, $gameId, $teamAUsers, $teamBUsers, $teamAMemberIds] = $this->createLiveHandBrainGame();

        // Simulate need to assign roles again
        $game = $em->getRepository(Game::class)->find($gameId);
        $game->resetHandBrainState();
        $em->flush();

        $this->loginClient($client, $teamAUsers[0]);

        $client->request('POST', '/games/'.$gameId.'/hand-brain/enable');
        $this->assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($gameId, $data['gameId']);
        self::assertSame('brain', $data['currentRole']);
        self::assertNull($data['pieceHint']);
        self::assertNotNull($data['brainMemberId']);
        self::assertNotNull($data['handMemberId']);
        self::assertSame($teamAMemberIds[1], $data['brainMemberId']);
        self::assertSame($teamAMemberIds[0], $data['handMemberId']);

        $em->clear();
        $game = $em->getRepository(Game::class)->find($gameId);
        self::assertSame('brain', $game->getHandBrainCurrentRole());
        self::assertNull($game->getHandBrainPieceHint());
        self::assertSame($data['brainMemberId'], $game->getHandBrainBrainMemberId());
        self::assertSame($data['handMemberId'], $game->getHandBrainHandMemberId());
    }

    public function testEnableHandBrainModeDeniedForOpponents(): void
    {
        [$client, $em, $gameId, $teamAUsers, $teamBUsers] = $this->createLiveHandBrainGame();

        $game = $em->getRepository(Game::class)->find($gameId);
        $game->resetHandBrainState();
        $em->flush();

        $this->loginClient($client, $teamBUsers[0]);

        $client->request('POST', '/games/'.$gameId.'/hand-brain/enable');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testSetHintRequiresBrainRole(): void
    {
        [$client, $em, $gameId, $teamAUsers, $teamBUsers, $teamAMemberIds] = $this->createLiveHandBrainGame();

        $game = $em->getRepository(Game::class)->find($gameId);
        $game->resetHandBrainState();
        $em->flush();

        $this->loginClient($client, $teamAUsers[0]);
        $client->request('POST', '/games/'.$gameId.'/hand-brain/enable');
        $this->assertResponseIsSuccessful();

        $state = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $brainMemberId = $teamAMemberIds[1];
        self::assertSame($state['brainMemberId'], $brainMemberId);

        $this->loginClient($client, $teamAUsers[1]);
        $client->request(
            'POST',
            '/games/'.$gameId.'/hand-brain/hint',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['piece' => 'knight'], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseIsSuccessful();

        $response = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('hand', $response['currentRole']);
        self::assertSame('knight', $response['pieceHint']);
        self::assertSame($brainMemberId, $response['brainMemberId']);

        $em->clear();
        $game = $em->getRepository(Game::class)->find($gameId);
        self::assertSame('knight', $game->getHandBrainPieceHint());
        self::assertSame('hand', $game->getHandBrainCurrentRole());

        // Opponent cannot set hint
        $this->loginClient($client, $teamAUsers[0]);
        $client->request(
            'POST',
            '/games/'.$gameId.'/hand-brain/hint',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['piece' => 'pawn'], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(409); // already waiting for hand

        $this->loginClient($client, $teamAUsers[1]);
        $client->request(
            'POST',
            '/games/'.$gameId.'/hand-brain/hint',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['piece' => 'bishop'], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(409);
    }

    /**
     * @return array{0: \Symfony\Bundle\FrameworkBundle\KernelBrowser, 1: EntityManagerInterface, 2: string, 3: array{User, User}, 4: array{User, User}, 5: array{string, string}, 6: array{string, string}}
     */
    private function createLiveHandBrainGame(): array
    {
        $client = self::createClient();
        $container = self::getContainer();
        $em = $container->get('doctrine')->getManager();

        $users = [];
        for ($i = 0; $i < 4; ++$i) {
            $user = new User();
            $user->setEmail(sprintf('hb%d+%s@test.io', $i, bin2hex(random_bytes(3))));
            $user->setPassword(password_hash('x', PASSWORD_BCRYPT));
            $em->persist($user);
            $users[] = $user;
        }
        $em->flush();

        /** @var CreateGameHandler $create */
        $create = $container->get(CreateGameHandler::class);
        $out = $create(new CreateGameInput($users[0]->getId() ?? '', 60, 'private', 'hand_brain'), $users[0]);

        /** @var Game $game */
        $game = $em->getRepository(Game::class)->find($out->gameId);
        $teams = $container->get(TeamRepositoryInterface::class);
        $teamA = $teams->findOneByGameAndName($game, Team::NAME_A);
        $teamB = $teams->findOneByGameAndName($game, Team::NAME_B);

        /** @var TeamMemberRepositoryInterface $members */
        $members = $container->get(TeamMemberRepositoryInterface::class);
        $teamAPlayers = [
            new TeamMember($teamA, $users[0], 0),
            new TeamMember($teamA, $users[1], 1),
        ];
        $teamBPlayers = [
            new TeamMember($teamB, $users[2], 0),
            new TeamMember($teamB, $users[3], 1),
        ];
        foreach ([$teamAPlayers, $teamBPlayers] as $group) {
            foreach ($group as $member) {
                $member->setReadyToStart(true);
                $members->add($member);
            }
        }
        $em->flush();

        /** @var StartGameHandler $start */
        $start = $container->get(StartGameHandler::class);
        $start(new StartGameInput($game->getId(), $users[0]->getId() ?? ''), $users[0]);
        $em->refresh($game);

        return [
            $client,
            $em,
            $game->getId(),
            [$users[0], $users[1]],
            [$users[2], $users[3]],
            [$teamAPlayers[0]->getId(), $teamAPlayers[1]->getId()],
            [$teamBPlayers[0]->getId(), $teamBPlayers[1]->getId()],
        ];
    }
}
