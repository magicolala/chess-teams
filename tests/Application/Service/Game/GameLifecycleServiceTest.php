<?php

namespace App\Tests\Application\Service\Game;

use App\Application\Service\Game\DTO\GameStartSummary;
use App\Application\Service\Game\GameLifecycleService;
use App\Application\Service\Werewolf\WerewolfRoleAssigner;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class GameLifecycleServiceTest extends TestCase
{
    public function testStartGameTransitionsStateAndAssignsWerewolfRoles(): void
    {
        $game = $this->createLobbyGame('werewolf');
        $teamA = new Team($game, Team::NAME_A);
        $teamB = new Team($game, Team::NAME_B);

        $teamRepo = $this->createMock(TeamRepositoryInterface::class);
        $teamRepo->method('findOneByGameAndName')->willReturnMap([
            [$game, Team::NAME_A, $teamA],
            [$game, Team::NAME_B, $teamB],
        ]);

        $memberRepo = $this->createMock(TeamMemberRepositoryInterface::class);
        $memberRepo->method('countActiveByTeam')->willReturnMap([
            [$teamA, 2],
            [$teamB, 2],
        ]);
        $memberRepo->method('areAllActivePlayersReady')->with($game)->willReturn(true);

        $usersA = [$this->createUser('alice@example.com'), $this->createUser('bob@example.com')];
        $usersB = [$this->createUser('carol@example.com'), $this->createUser('dave@example.com')];
        $membersA = [];
        foreach ($usersA as $idx => $user) {
            $membersA[] = new TeamMember($teamA, $user, $idx);
        }
        $membersB = [];
        foreach ($usersB as $idx => $user) {
            $membersB[] = new TeamMember($teamB, $user, $idx);
        }
        $memberRepo->method('findActiveOrderedByTeam')->willReturnMap([
            [$teamA, $membersA],
            [$teamB, $membersB],
        ]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $assigner = $this->createMock(WerewolfRoleAssigner::class);
        $assigner->expects($this->once())
            ->method('assignForGame')
            ->with($game, $this->callback(fn ($list) => 2 === count($list)), $this->callback(fn ($list) => 2 === count($list)));

        $service = new GameLifecycleService($teamRepo, $memberRepo, $em, $assigner);
        $summary = $service->start($game, $this->creator);

        $this->assertInstanceOf(GameStartSummary::class, $summary);
        $this->assertSame(Game::STATUS_LIVE, $game->getStatus());
        $this->assertSame(Game::TEAM_A, $summary->turnTeam);
        $this->assertGreaterThan(new \DateTimeImmutable('+13 days'), $summary->turnDeadline);
        $this->assertSame($summary->turnDeadline, $game->getTurnDeadline());
    }

    public function testStartGameAssignsHandBrainRolesForStartingTeam(): void
    {
        $game = $this->createLobbyGame('hand_brain');
        $teamA = new Team($game, Team::NAME_A);
        $teamB = new Team($game, Team::NAME_B);

        $teamRepo = $this->createMock(TeamRepositoryInterface::class);
        $teamRepo->method('findOneByGameAndName')->willReturnMap([
            [$game, Team::NAME_A, $teamA],
            [$game, Team::NAME_B, $teamB],
        ]);

        $membersA = [];
        for ($i = 0; $i < 3; ++$i) {
            $membersA[] = new TeamMember($teamA, $this->createUser(sprintf('member-a%d@example.com', $i)), $i);
        }

        $membersB = [];
        for ($i = 0; $i < 2; ++$i) {
            $membersB[] = new TeamMember($teamB, $this->createUser(sprintf('member-b%d@example.com', $i)), $i);
        }

        $memberRepo = $this->createMock(TeamMemberRepositoryInterface::class);
        $memberRepo->method('countActiveByTeam')->willReturnMap([
            [$teamA, count($membersA)],
            [$teamB, count($membersB)],
        ]);
        $memberRepo->method('areAllActivePlayersReady')->with($game)->willReturn(true);
        $memberRepo->method('findActiveOrderedByTeam')->willReturnMap([
            [$teamA, $membersA],
            [$teamB, $membersB],
        ]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $assigner = $this->createMock(WerewolfRoleAssigner::class);
        $assigner->expects($this->never())->method('assignForGame');

        $service = new GameLifecycleService($teamRepo, $memberRepo, $em, $assigner);
        $service->start($game, $this->creator);

        $this->assertSame('brain', $game->getHandBrainCurrentRole());
        $this->assertNull($game->getHandBrainPieceHint());
        $this->assertSame($membersA[1]->getId(), $game->getHandBrainBrainMemberId());
        $this->assertSame($membersA[0]->getId(), $game->getHandBrainHandMemberId());
    }

    public function testStartGameFailsForNonCreator(): void
    {
        $game = $this->createLobbyGame();
        $teamRepo = $this->createMock(TeamRepositoryInterface::class);
        $memberRepo = $this->createMock(TeamMemberRepositoryInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $assigner = $this->createMock(WerewolfRoleAssigner::class);

        $service = new GameLifecycleService($teamRepo, $memberRepo, $em, $assigner);

        $this->expectException(AccessDeniedHttpException::class);

        $service->start($game, $this->createUser('hacker@example.com'));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->creator = $this->createUser('creator@example.com');
    }

    private User $creator;

    private function createLobbyGame(string $mode = 'classic'): Game
    {
        $game = new Game();
        $game->setStatus(Game::STATUS_LOBBY);
        $game->setCreatedBy($this->creator);
        $game->setMode($mode);

        return $game;
    }

    private function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);

        return $user;
    }
}
