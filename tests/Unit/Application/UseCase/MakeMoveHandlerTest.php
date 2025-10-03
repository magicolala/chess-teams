<?php

namespace App\Tests\Unit\Application\UseCase;

use App\Application\DTO\MakeMoveInput;
use App\Application\Service\Game\DTO\MoveResult;
use App\Application\Service\Game\GameMoveServiceInterface;
use App\Application\UseCase\MakeMoveHandler;
use App\Entity\Game;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MakeMoveHandlerTest extends TestCase
{
    public function testMakeMoveHappyPath(): void
    {
        $service = $this->createMock(GameMoveServiceInterface::class);

        $handler = new MakeMoveHandler($service);

        $uA = new User();
        $uA->setEmail('a@test.io');
        $uA->setPassword('x');
        $game = (new Game())
            ->setStatus(Game::STATUS_LIVE)
            ->setTurnTeam(Game::TEAM_B)
            ->setFen('fen-after')
            ->setTurnDeadline(new \DateTimeImmutable('+15 minutes'))
            ->setPly(1)
        ;

        $result = new MoveResult($game, 42, new \DateTimeImmutable());

        $service->expects($this->once())
            ->method('play')
            ->with($this->isInstanceOf(MakeMoveInput::class), $uA)
            ->willReturn($result);

        $out = $handler(new MakeMoveInput('game-id', 'e2e4', $uA->getId() ?? ''), $uA);

        self::assertSame(42, $out->ply);
        self::assertSame(Game::TEAM_B, $out->turnTeam);
        self::assertSame('fen-after', $out->fen);
        self::assertSame($game->getTurnDeadline()?->getTimestamp() * 1000, $out->turnDeadlineTs);
    }
}
