<?php

namespace App\Tests\Unit\Application\UseCase;

use App\Application\DTO\TimeoutTickInput;
use App\Application\UseCase\TimeoutTickHandler;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\MoveRepositoryInterface;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class TimeoutTickHandlerTest extends TestCase
{
    public function testAppliesTimeoutAndSwitchesTeam(): void
    {
        $games   = $this->createMock(GameRepositoryInterface::class);
        $teams   = $this->createMock(TeamRepositoryInterface::class);
        $members = $this->createMock(TeamMemberRepositoryInterface::class);
        $moves   = $this->createMock(MoveRepositoryInterface::class);
        $em      = $this->createMock(EntityManagerInterface::class);

        $lock = $this->createMock(LockInterface::class);
        $lock->method('acquire')->willReturn(true);
        $lock->expects(self::once())->method('release');
        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->method('createLock')->willReturn($lock);

        $handler = new TimeoutTickHandler($games, $teams, $members, $moves, $lockFactory, $em);

        $creator = new User();
        $creator->setEmail('c@test.io');
        $creator->setPassword('x');
        $g = (new Game())
            ->setCreatedBy($creator)
            ->setStatus(Game::STATUS_LIVE)
            ->setTurnTeam(Team::NAME_A)
            ->setTurnDurationSec(60)
            ->setFen('startpos')
            ->setPly(0)
            ->setTurnDeadline((new \DateTimeImmutable())->modify('-1 second'))
        ;

        $games->method('get')->willReturn($g);

        $ta = new Team($g, Team::NAME_A);
        $tb = new Team($g, Team::NAME_B);
        $teams->method('findOneByGameAndName')->willReturnMap([
            [$g, Team::NAME_A, $ta],
            [$g, Team::NAME_B, $tb],
        ]);

        $uA = new User();
        $uA->setEmail('a@test.io');
        $uA->setPassword('x');
        $members->method('findActiveOrderedByTeam')->with($ta)->willReturn([new TeamMember($ta, $uA, 0)]);

        $moves->expects(self::once())->method('add');
        $em->expects(self::once())->method('flush');

        $out = $handler(new TimeoutTickInput($g->getId(), $creator->getId() ?? ''), $creator);

        self::assertTrue($out->timedOutApplied);
        self::assertSame('B', $out->turnTeam);
        self::assertSame(1, $out->ply);
    }
}
