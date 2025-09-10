<?php

namespace App\Tests\Unit\Application\UseCase;

use App\Application\DTO\MarkPlayerReadyInput;
use App\Application\UseCase\MarkPlayerReadyHandler;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Entity\Game;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MarkPlayerReadyHandlerTest extends TestCase
{
    public function testMarkPlayerReadyHappyPath(): void
    {
        $games = $this->createMock(GameRepositoryInterface::class);
        $members = $this->createMock(TeamMemberRepositoryInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $handler = new MarkPlayerReadyHandler($games, $members, $em);

        $user = new User();
        $user->setEmail('player@test.io');
        $user->setPassword('x');

        $g = (new Game())
            ->setStatus(Game::STATUS_LOBBY)
            ->setTurnTeam(Team::NAME_A)
        ;

        $games->method('get')->willReturn($g);

        $team = new Team($g, Team::NAME_A);
        $member = new TeamMember($team, $user, 0);
        $members->method('findOneByGameAndUser')->with($g, $user)->willReturn($member);

        $members->method('countReadyByGame')->with($g)->willReturn(1);
        $members->method('countActiveByGame')->with($g)->willReturn(1);
        $members->method('areAllActivePlayersReady')->with($g)->willReturn(true);

        $em->expects(self::once())->method('flush');

        $in = new MarkPlayerReadyInput($g->getId(), $user->getId() ?? '', true);
        $out = $handler($in, $user);

        self::assertSame($g->getId(), $out->gameId);
        self::assertSame($user->getId(), $out->userId);
        self::assertTrue($out->ready);
        self::assertTrue($out->allPlayersReady);
        self::assertSame(1, $out->readyPlayersCount);
        self::assertSame(1, $out->totalPlayersCount);
    }
}
