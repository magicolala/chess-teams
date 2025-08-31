<?php

namespace App\Tests\Unit\Application\UseCase;

use App\Application\DTO\ListMovesInput;
use App\Application\UseCase\ListMovesHandler;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\MoveRepositoryInterface;
use App\Entity\Game;
use App\Entity\Move;
use App\Entity\Team;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ListMovesHandlerTest extends TestCase
{
    public function testReturnsMovesProjection(): void
    {
        $games = $this->createMock(GameRepositoryInterface::class);
        $movesRepo = $this->createMock(MoveRepositoryInterface::class);

        $handler = new ListMovesHandler($games, $movesRepo);

        $g = (new Game())->setFen('startpos')->setPly(0);
        $games->method('get')->willReturn($g);

        $tA = new Team($g, Team::NAME_A);
        $uA = new User();
        $uA->setEmail('a@test.io');
        $uA->setPassword('x');

        $m1 = new Move($g, 1);
        $m1->setTeam($tA)->setByUser($uA)->setUci('e2e4')->setSan('E2E4')->setFenAfter('startpos|e2e4');

        $movesRepo->method('listByGameOrdered')->with($g)->willReturn([$m1]);

        $out = $handler(new ListMovesInput($g->getId()));
        self::assertSame($g->getId(), $out->gameId);
        self::assertCount(1, $out->moves);
        self::assertSame('e2e4', $out->moves[0]['uci']);
    }
}
