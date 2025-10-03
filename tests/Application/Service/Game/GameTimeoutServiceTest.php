<?php

namespace App\Tests\Application\Service\Game;

use App\Application\DTO\TimeoutTickInput;
use App\Application\Service\Game\GameTimeoutService;
use App\Application\Service\GameEndEvaluator;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\MoveRepositoryInterface;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\Move;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

final class GameTimeoutServiceTest extends TestCase
{
    public function testNoTimeoutWhenDeadlineNotReached(): void
    {
        $game = $this->createLiveGame();
        $game->setTurnDeadline(new \DateTimeImmutable('+2 hours'));

        $games = $this->createMock(GameRepositoryInterface::class);
        $games->method('get')->with($game->getId())->willReturn($game);

        $teams = $this->createMock(TeamRepositoryInterface::class);
        $members = $this->createMock(TeamMemberRepositoryInterface::class);
        $moves = $this->createMock(MoveRepositoryInterface::class);
        $moves->expects($this->never())->method('add');

        $lock = $this->createConfiguredMock(LockInterface::class, ['acquire' => true]);
        $lock->expects($this->once())->method('release');
        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->method('createLock')->willReturn($lock);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $service = new GameTimeoutService(
            $games,
            $teams,
            $members,
            $moves,
            $lockFactory,
            $em,
            new GameEndEvaluator(),
        );

        $input = new TimeoutTickInput($game->getId());
        $result = $service->handle($input);

        $this->assertFalse($result->timeoutTriggered);
        $this->assertSame(0, $result->ply);
    }

    public function testTimeoutCreatesMoveAndUpdatesState(): void
    {
        $game = $this->createLiveGame();
        $game->setTurnDeadline(new \DateTimeImmutable('-5 minutes'));
        $teamA = new Team($game, Team::NAME_A);
        $teamB = new Team($game, Team::NAME_B);
        $member = new TeamMember($teamA, $this->createUser(), 0);

        $games = $this->createMock(GameRepositoryInterface::class);
        $games->method('get')->with($game->getId())->willReturn($game);

        $teams = $this->createMock(TeamRepositoryInterface::class);
        $teams->method('findOneByGameAndName')->willReturnMap([
            [$game, Team::NAME_A, $teamA],
            [$game, Team::NAME_B, $teamB],
        ]);

        $members = $this->createMock(TeamMemberRepositoryInterface::class);
        $members->method('findActiveOrderedByTeam')->with($teamA)->willReturn([$member]);

        $moves = $this->createMock(MoveRepositoryInterface::class);
        $moves->expects($this->once())
            ->method('add')
            ->with($this->callback(static function (Move $move): bool {
                return Move::TYPE_TIMEOUT === $move->getType();
            }));

        $lock = $this->createConfiguredMock(LockInterface::class, ['acquire' => true]);
        $lock->expects($this->once())->method('release');
        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->method('createLock')->willReturn($lock);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $service = new GameTimeoutService(
            $games,
            $teams,
            $members,
            $moves,
            $lockFactory,
            $em,
            new GameEndEvaluator(),
        );

        $result = $service->handle(new TimeoutTickInput($game->getId()));

        $this->assertTrue($result->timeoutTriggered);
        $this->assertSame(1, $result->ply);
        $this->assertTrue($game->isTimeoutDecisionPending());
        $this->assertSame(Game::TEAM_A, $game->getTimeoutTimedOutTeam());
    }

    private function createLiveGame(): Game
    {
        $game = new Game();
        $game->setStatus(Game::STATUS_LIVE);
        $game->setTurnTeam(Game::TEAM_A);

        return $game;
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('timeout@example.com');

        return $user;
    }
}
