<?php

namespace App\Tests\Unit\Application\UseCase;

use App\Application\DTO\TimeoutDecisionInput;
use App\Application\UseCase\DecideTimeoutHandler;
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
final class DecideTimeoutHandlerTest extends TestCase
{
    private function makeHandler(
        &$game,
        &$teams,
        &$members,
        &$user,
        string $turnTeam = Team::NAME_A,
        string $timedOutTeam = Game::TEAM_A,
        string $decisionTeam = Game::TEAM_B,
    ): DecideTimeoutHandler {
        $games = $this->createMock(GameRepositoryInterface::class);
        $teams = $this->createMock(TeamRepositoryInterface::class);
        $members = $this->createMock(TeamMemberRepositoryInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $lock = $this->createMock(LockInterface::class);
        $lock->method('acquire')->willReturn(true);
        $lock->expects(self::once())->method('release');
        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->method('createLock')->willReturn($lock);

        $handler = new DecideTimeoutHandler($games, $teams, $members, $lockFactory, $em);

        $user = new User();
        $user->setEmail('p@test.io');
        $user->setPassword('x');

        $game = (new Game())
            ->setStatus(Game::STATUS_LIVE)
            ->setTurnTeam($turnTeam)
            ->setTimeoutDecisionPending(true)
            ->setTimeoutTimedOutTeam($timedOutTeam)
            ->setTimeoutDecisionTeam($decisionTeam)
            ->setTurnDeadline((new \DateTimeImmutable())->modify('+1 day'))
        ;

        $games->method('get')->willReturn($game);

        $teamA = new Team($game, Team::NAME_A);
        $teamB = new Team($game, Team::NAME_B);

        $teams->method('findOneByGameAndName')->willReturnMap([
            [$game, Team::NAME_A, $teamA],
            [$game, Team::NAME_B, $teamB],
        ]);

        // Membership of deciding user in the right team
        $userTeam = Game::TEAM_A === $decisionTeam ? $teamA : $teamB;
        $members->method('findOneByGameAndUser')->with($game, $user)->willReturn(new TeamMember($userTeam, $user, 0));

        $em->expects(self::once())->method('flush');

        return $handler;
    }

    public function testDecisionEndFinishesGame(): void
    {
        $handler = $this->makeHandler($g, $teams, $members, $user, turnTeam: Team::NAME_A, timedOutTeam: Game::TEAM_A, decisionTeam: Game::TEAM_B);

        $out = $handler(new TimeoutDecisionInput($g->getId(), $user->getId() ?? '', 'end'), $user);

        self::assertSame(Game::STATUS_FINISHED, $out->status);
        self::assertSame('B+Atimeout', $out->result);
        self::assertFalse($out->decisionPending);
        self::assertNull($out->turnTeam);
        self::assertNull($out->turnDeadlineTs);
        self::assertSame(Game::STATUS_FINISHED, $g->getStatus());
        self::assertNull($g->getTurnDeadline());
        self::assertFalse($g->isTimeoutDecisionPending());
    }

    public function testDecisionAllowNextKeepsTimedOutTeamAndRestoresDeadline(): void
    {
        $handler = $this->makeHandler($g, $teams, $members, $user, turnTeam: Team::NAME_A, timedOutTeam: Game::TEAM_A, decisionTeam: Game::TEAM_B);

        // Ensure there is at least one active member to rotate index
        $teamTimedOut = new Team($g, Team::NAME_A);
        $members->method('findActiveOrderedByTeam')->willReturn([new TeamMember($teamTimedOut, $user, 0)]);

        $out = $handler(new TimeoutDecisionInput($g->getId(), $user->getId() ?? '', 'allow_next'), $user);

        self::assertSame(Game::STATUS_LIVE, $out->status);
        self::assertFalse($out->decisionPending);
        self::assertSame(Team::NAME_A, $out->turnTeam, 'It should remain the timed-out team to play');
        self::assertFalse($g->isTimeoutDecisionPending());
        self::assertSame(Team::NAME_A, $g->getTurnTeam());

        $deadline = $g->getTurnDeadline();
        self::assertInstanceOf(\DateTimeInterface::class, $deadline);
        self::assertSame($deadline->getTimestamp() * 1000, $out->turnDeadlineTs);
    }
}
