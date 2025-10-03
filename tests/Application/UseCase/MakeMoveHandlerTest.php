<?php

namespace App\Tests\Application\UseCase;

use App\Application\DTO\MakeMoveInput;
use App\Application\Service\Game\DTO\MoveResult;
use App\Application\Service\Game\GameMoveServiceInterface;
use App\Application\UseCase\MakeMoveHandler;
use App\Entity\Game;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class MakeMoveHandlerTest extends TestCase
{
    public function testHandlerDelegatesToService(): void
    {
        $service = $this->createMock(GameMoveServiceInterface::class);
        $handler = new MakeMoveHandler($service);

        $input = new MakeMoveInput('game-1', 'e2e4', 'user-1');
        $user = $this->createConfiguredMock(User::class, ['getId' => 'user-1']);

        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn('game-1');
        $game->method('getTurnTeam')->willReturn('A');
        $game->method('getFen')->willReturn('fen-after');
        $deadline = new \DateTimeImmutable('+1 day');
        $game->method('getTurnDeadline')->willReturn($deadline);

        $service->expects($this->once())
            ->method('play')
            ->with($input, $user)
            ->willReturn(new MoveResult($game, 42, new \DateTimeImmutable()));

        $output = $handler($input, $user);

        $this->assertSame('game-1', $output->gameId);
        $this->assertSame(42, $output->ply);
        $this->assertSame('A', $output->turnTeam);
        $this->assertSame($deadline->getTimestamp() * 1000, $output->turnDeadlineTs);
        $this->assertSame('fen-after', $output->fen);
    }
}
