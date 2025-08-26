<?php

namespace App\Tests\Unit\Application\UseCase;

use App\Application\DTO\StartGameInput;
use App\Application\UseCase\StartGameHandler;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class StartGameHandlerTest extends TestCase
{
    public function testStartGameSetsLiveAndDeadline(): void
    {
        $games   = $this->createMock(GameRepositoryInterface::class);
        $teams   = $this->createMock(TeamRepositoryInterface::class);
        $members = $this->createMock(TeamMemberRepositoryInterface::class);
        $em      = $this->createMock(EntityManagerInterface::class);

        $handler = new StartGameHandler($games, $teams, $members, $em);

        $creator = new User();
        $creator->setEmail('creator@test.io');
        $creator->setPassword('x');

        $g = (new Game())
            ->setCreatedBy($creator)
            ->setTurnDurationSec(60)
            ->setStatus(Game::STATUS_LOBBY)
        ;

        $games->method('get')->willReturn($g);

        $ta = new Team($g, Team::NAME_A);
        $tb = new Team($g, Team::NAME_B);

        $teams->method('findOneByGameAndName')->willReturnMap([
            [$g, Team::NAME_A, $ta],
            [$g, Team::NAME_B, $tb],
        ]);

        $members->method('countActiveByTeam')->willReturn(1);
        $em->expects(self::once())->method('flush');

        $out = $handler(new StartGameInput($g->getId(), $creator->getId() ?? ''), $creator);

        self::assertSame('live', $out->status);
        self::assertSame('A', $out->turnTeam);
        self::assertGreaterThan(time() * 1000, $out->turnDeadlineTs - 1000); // ~now+60s
    }
}
