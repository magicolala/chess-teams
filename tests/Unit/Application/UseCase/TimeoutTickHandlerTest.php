<?php

namespace App\Tests\Unit\Application\UseCase;

use App\Application\DTO\TimeoutTickInput;
use App\Application\Service\Game\DTO\TimeoutResult;
use App\Application\Service\Game\GameTimeoutService;
use App\Application\UseCase\TimeoutTickHandler;
use App\Entity\Game;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \App\Application\UseCase\TimeoutTickHandler
 */
final class TimeoutTickHandlerTest extends TestCase
{
    public function testHandlerBuildsOutputFromServiceResult(): void
    {
        $service = $this->createMock(GameTimeoutService::class);
        $handler = new TimeoutTickHandler($service);

        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn('game-x');
        $game->method('getTurnTeam')->willReturn('A');
        $game->method('getFen')->willReturn('fen');
        $deadline = new \DateTimeImmutable('+5 minutes');
        $game->method('getTurnDeadline')->willReturn($deadline);

        $result = new TimeoutResult($game, 99, true, new \DateTimeImmutable());

        $input = new TimeoutTickInput('game-x');
        $user = $this->createMock(User::class);

        $service->expects($this->once())
            ->method('handle')
            ->with($input)
            ->willReturn($result);

        $output = $handler($input, $user);

        $this->assertTrue($output->timedOutApplied);
        $this->assertSame(99, $output->ply);
        $this->assertSame('A', $output->turnTeam);
        $this->assertSame($deadline->getTimestamp() * 1000, $output->turnDeadlineTs);
        $this->assertSame('fen', $output->fen);
    }
}
