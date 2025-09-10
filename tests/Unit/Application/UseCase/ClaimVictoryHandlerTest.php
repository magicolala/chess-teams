<?php

namespace App\Tests\Unit\Application\UseCase;

use App\Application\DTO\ClaimVictoryInput;
use App\Application\UseCase\ClaimVictoryHandler;
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
final class ClaimVictoryHandlerTest extends TestCase
{
    public function testClaimVictoryAfter3ConsecutiveTimeouts(): void
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

        $handler = new ClaimVictoryHandler($games, $teams, $members, $lockFactory, $em);

        $creator = new User();
        $creator->setEmail('creator@test.io');
        $creator->setPassword('x');

        $claimer = new User();
        $claimer->setEmail('claimer@test.io');
        $claimer->setPassword('x');

        $g = (new Game())
            ->setStatus(Game::STATUS_LIVE)
            ->setTurnTeam(Game::TEAM_A)
            ->setConsecutiveTimeouts(3)
            ->setLastTimeoutTeam(Game::TEAM_A) // A a fait les timeouts -> B peut revendiquer
        ;

        $games->method('get')->willReturn($g);

        $teamA = new Team($g, Team::NAME_A);
        $teamB = new Team($g, Team::NAME_B);

        // Claimer est dans l'équipe B (celle autorisée à revendiquer)
        $memberB = new TeamMember($teamB, $claimer, 0);
        $members->method('findOneByGameAndUser')->with($g, $claimer)->willReturn($memberB);

        $em->expects(self::once())->method('flush');

        $out = $handler(new ClaimVictoryInput($g->getId(), $claimer->getId() ?? ''), $claimer);

        self::assertTrue($out->claimed);
        self::assertSame(Game::STATUS_FINISHED, $out->status);
        self::assertNotNull($out->result);
        self::assertSame(Game::TEAM_B, $out->winnerTeam);
        self::assertSame(Game::STATUS_FINISHED, $g->getStatus());
        self::assertNotNull($g->getResult());
        self::assertNull($g->getTurnDeadline());
    }
}
