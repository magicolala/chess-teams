<?php

namespace App\Tests\Unit\Application\UseCase;

use App\Application\DTO\ShowGameInput;
use App\Application\UseCase\ShowGameHandler;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ShowGameHandlerTest extends TestCase
{
    public function testReturnsProjectionWithMembers(): void
    {
        $games = $this->createMock(GameRepositoryInterface::class);
        $teams = $this->createMock(TeamRepositoryInterface::class);
        $members = $this->createMock(TeamMemberRepositoryInterface::class);

        $handler = new ShowGameHandler($games, $teams, $members);

        $g = (new Game())->setStatus('lobby')->setFen('startpos')->setPly(0)->setTurnTeam('A');

        $games->method('get')->willReturn($g);

        $ta = new Team($g, Team::NAME_A);
        $tb = new Team($g, Team::NAME_B);

        $teams->method('findOneByGameAndName')->willReturnMap([
            [$g, Team::NAME_A, $ta],
            [$g, Team::NAME_B, $tb],
        ]);

        $u1 = new User();
        $u1->setEmail('a@test.io');
        $u1->setPassword('x');
        $u2 = new User();
        $u2->setEmail('b@test.io');
        $u2->setPassword('x');

        $m1 = new TeamMember($ta, $u1, 0);
        $m2 = new TeamMember($tb, $u2, 0);

        $members->method('findActiveOrderedByTeam')->willReturnMap([
            [$ta, [$m1]],
            [$tb, [$m2]],
        ]);

        $out = $handler(new ShowGameInput($g->getId()));
        self::assertSame($g->getId(), $out->id);
        self::assertSame('startpos', $out->fen);
        self::assertSame('A', $out->turnTeam);
        self::assertCount(1, $out->teamA['members']);
        self::assertCount(1, $out->teamB['members']);
    }
}
