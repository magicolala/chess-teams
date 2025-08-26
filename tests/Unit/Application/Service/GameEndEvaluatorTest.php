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
    public function testCheckmateSetsFinishedAndWinner(): void
    {
        $svc = new GameEndEvaluator();
        $g   = (new Game())->setStatus('live');

        // Position de mat : FEN after smothered mate par ex.
        // Ici on force une FEN de mat simple (roi noir mat). Exemple:
        // "rnb1kbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq - 1 3"
        // (à adapter si besoin selon la lib)
        $g->setFen('rnb1kbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq - 1 3');

        $res = $svc->evaluateAndApply($g);
        self::assertTrue($res['isOver']);
        self::assertSame('finished', $g->getStatus());
        self::assertNotNull($g->getResult());
    }

    public function testDrawSetsHalf(): void
    {
        $svc = new GameEndEvaluator();
        $g   = (new Game())->setStatus('live');
        // Stalemate well-known FEN:
        $g->setFen('7k/5Q2/6K1/8/8/8/8/8 b - - 0 1');

        $res = $svc->evaluateAndApply($g);
        self::assertTrue($res['isOver']);
        self::assertSame('finished', $g->getStatus());
        self::assertSame('1/2-1/2', $g->getResult());
    }
}
