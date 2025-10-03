<?php

namespace App\Tests\Unit\Application\UseCase;

use App\Application\DTO\StartGameInput;
use App\Application\Service\Game\DTO\GameStartSummary;
use App\Application\Service\Game\GameLifecycleService;
use App\Application\UseCase\StartGameHandler;
use App\Domain\Repository\GameRepositoryInterface;
use App\Entity\Game;
use App\Entity\User;
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
        $games = $this->createMock(GameRepositoryInterface::class);
        $lifecycle = $this->createMock(GameLifecycleService::class);

        $handler = new StartGameHandler($games, $lifecycle);

        $creator = new User();
        $creator->setEmail('creator@test.io');
        $creator->setPassword('x');

        $g = (new Game())
            ->setCreatedBy($creator)
            ->setTurnDurationSec(60)
            ->setStatus(Game::STATUS_LOBBY)
        ;

        $games->method('get')->willReturn($g);

        $deadline = new \DateTimeImmutable('+60 seconds');
        $lifecycle->expects($this->once())
            ->method('start')
            ->with($g, $creator)
            ->willReturn(new GameStartSummary(
                gameId: $g->getId(),
                status: Game::STATUS_LIVE,
                turnTeam: Game::TEAM_A,
                turnDeadline: $deadline,
            ));

        $out = $handler(new StartGameInput($g->getId(), $creator->getId() ?? ''), $creator);

        self::assertSame('live', $out->status);
        self::assertSame('A', $out->turnTeam);
        self::assertSame($deadline->getTimestamp() * 1000, $out->turnDeadlineTs);
    }
}
