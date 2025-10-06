<?php

namespace App\Tests\Unit\Application\UseCase;

use App\Application\DTO\TimeoutTickInput;
use App\Application\Service\Game\DTO\TimeoutResult;
use App\Application\Service\Game\GameTimeoutServiceInterface;
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
        $service = $this->createMock(GameTimeoutServiceInterface::class);
        $handler = new TimeoutTickHandler($service);

        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn('game-x');
        $game->method('getTurnTeam')->willReturn('A');
        $game->method('getFen')->willReturn('fen');
        $deadline = new \DateTimeImmutable('+5 minutes');
        $game->method('getTurnDeadline')->willReturn($deadline);
        $game->method('getHandBrainCurrentRole')->willReturn('hand');
        $game->method('getHandBrainPieceHint')->willReturn('pawn');
        $game->method('getHandBrainBrainMemberId')->willReturn('brain-1');
        $game->method('getHandBrainHandMemberId')->willReturn('hand-1');

        $result = new TimeoutResult($game, 99, true, new \DateTimeImmutable());

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn('user-x');
        $input = new TimeoutTickInput('game-x', 'user-x');

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
        $this->assertSame('hand', $output->handBrainCurrentRole);
        $this->assertSame('pawn', $output->handBrainPieceHint);
        $this->assertSame('brain-1', $output->handBrainBrainMemberId);
        $this->assertSame('hand-1', $output->handBrainHandMemberId);
    }
}
