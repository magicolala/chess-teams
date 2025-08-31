<?php

namespace App\Tests\Unit\Application\Service;

use App\Application\Service\GameEndEvaluator;
use App\Entity\Game;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class GameEndEvaluatorTest extends TestCase
{
    public function testBasicGameEndEvaluationReturnsOngoing(): void
    {
        $svc = new GameEndEvaluator();
        $g = (new Game())->setStatus('live')->setResult(null);

        // Test with a regular position - should return ongoing game
        $g->setFen('rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq e3 0 1');

        $res = $svc->evaluateAndApply($g);

        // Current simplified implementation returns ongoing game
        self::assertFalse($res['isOver']);
        self::assertSame('live', $g->getStatus());
        self::assertNull($g->getResult());
    }

    public function testEvaluatorHandlesStartpos(): void
    {
        $svc = new GameEndEvaluator();
        $g = (new Game())->setStatus('live')->setResult(null);

        // Test with startpos
        $g->setFen('startpos');

        $res = $svc->evaluateAndApply($g);

        // Current simplified implementation returns ongoing game
        self::assertFalse($res['isOver']);
        self::assertSame('live', $g->getStatus());
        self::assertNull($g->getResult());
    }
}
