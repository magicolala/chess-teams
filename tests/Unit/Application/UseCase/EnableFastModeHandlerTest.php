<?php

namespace App\Tests\Unit\Application\UseCase;

use App\Application\DTO\EnableFastModeInput;
use App\Application\UseCase\EnableFastModeHandler;
use App\Domain\Repository\GameRepositoryInterface;
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
final class EnableFastModeHandlerTest extends TestCase
{
    public function testEnableFastModeHappyPath(): void
    {
        $games = $this->createMock(GameRepositoryInterface::class);
        $teams = $this->createMock(TeamRepositoryInterface::class);
        $members = $this->createMock(TeamMemberRepositoryInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $lock = $this->createMock(LockInterface::class);
        $lock->method('acquire')->willReturn(true);
        $lock->expects(self::once())->method('release');
        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->method('createLock')->willReturn($lock);

        $handler = new EnableFastModeHandler($games, $teams, $members, $lockFactory, $em);

        $user = new User();
        $user->setEmail('x@test.io');
        $user->setPassword('x');

        $g = (new Game())
            ->setStatus(Game::STATUS_LIVE)
            ->setTurnTeam(Team::NAME_A)
            ->setTurnDeadline((new \DateTimeImmutable())->modify('+1 day'))
        ;

        $games->method('get')->willReturn($g);

        $ta = new Team($g, Team::NAME_A);
        $tb = new Team($g, Team::NAME_B);
        $teams->method('findOneByGameAndName')->willReturnMap([
            [$g, Team::NAME_A, $ta],
            [$g, Team::NAME_B, $tb],
        ]);

        $members->method('findActiveOrderedByTeam')->with($ta)->willReturn([
            new TeamMember($ta, $user, 0),
        ]);

        $em->expects(self::once())->method('flush');

        $turnDeadline = $g->getTurnDeadline();
        $expectedTurnDeadlineTs = $turnDeadline ? $turnDeadline->getTimestamp() * 1000 : 0;

        $out = $handler(new EnableFastModeInput($g->getId(), $user->getId() ?? ''), $user);

        self::assertTrue($out->enabled);
        self::assertGreaterThan(0, $out->fastModeDeadlineTs);
        // turn deadline is preserved (free mode)
        self::assertSame($expectedTurnDeadlineTs, $out->turnDeadlineTs);
        self::assertTrue($g->isFastModeEnabled(), 'Game should be in fast mode');
    }
}
