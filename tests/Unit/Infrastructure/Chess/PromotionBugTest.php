<?php

namespace App\Tests\Unit\Infrastructure\Chess;

use App\Infrastructure\Chess\PChessEngine;
use PHPUnit\Framework\TestCase;

/**
 * Tests spécifiques pour s'assurer que le bug de promotion est corrigé.
 * Le bug : le paramètre "promotion" était ajouté à tous les coups, même ceux qui n'en avaient pas besoin.
 *
 * @internal
 *
 * @coversNothing
 */
final class PromotionBugTest extends TestCase
{
    private PChessEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new PChessEngine();
    }

    public function testNormalPawnMoveDoesNotRequirePromotion(): void
    {
        // e2-e4 est un coup de pion normal, pas une promotion
        $result = $this->engine->applyUci('startpos', 'e2e4');

        self::assertArrayHasKey('fenAfter', $result);
        self::assertArrayHasKey('san', $result);
        self::assertNotEmpty($result['fenAfter']);
        self::assertNotEmpty($result['san']);

        // Le SAN ne devrait pas contenir de promotion
        self::assertStringNotContainsString('=', $result['san']);
    }

    public function testNormalKnightMoveDoesNotRequirePromotion(): void
    {
        // g1-f3 est un coup de cavalier, pas une promotion
        $result = $this->engine->applyUci('startpos', 'g1f3');

        self::assertArrayHasKey('fenAfter', $result);
        self::assertArrayHasKey('san', $result);
        self::assertNotEmpty($result['fenAfter']);
        self::assertNotEmpty($result['san']);

        // Le SAN ne devrait pas contenir de promotion
        self::assertStringNotContainsString('=', $result['san']);
    }

    public function testVariousNormalMovesWork(): void
    {
        // Only include moves that are legal from the initial position
        $normalMoves = [
            'e2e4', // pawn two squares
            'd2d3', // pawn one square
            'g1f3', // knight
            'b1c3', // knight
            'a2a3', // pawn
            'h2h3', // pawn
            'c2c4', // pawn
        ];

        foreach ($normalMoves as $uci) {
            $result = $this->engine->applyUci('startpos', $uci);

            self::assertArrayHasKey('fenAfter', $result);
            self::assertArrayHasKey('san', $result);
            self::assertNotEmpty($result['fenAfter']);
            self::assertNotEmpty($result['san']);

            // Aucun de ces coups ne devrait contenir de promotion
            self::assertStringNotContainsString(
                '=',
                $result['san'],
                "Le coup {$uci} ne devrait pas contenir de promotion"
            );
        }
    }

    public function testPromotionMoveIncludesPromotion(): void
    {
        // e7-e8q est un coup de promotion (pion blanc de la 7e à la 8e rangée)
        // Utiliser une FEN minimale où e7 est libre de promouvoir sans capture et e8 est vide
        // Board:
        // 8  . . . . k . . .
        // 7  . . . . P . . .
        // 6  . . . . . . . .
        // 5  . . . . . . . .
        // 4  . . . . . . . .
        // 3  . . . . . . . .
        // 2  . . . . . . . .
        // 1  . . . . K . . .
        //    a b c d e f g h
        $fenWithPawnOnSeventh = 'k7/4P3/8/8/8/8/8/4K3 w - - 0 1';

        $result = $this->engine->applyUci($fenWithPawnOnSeventh, 'e7e8q');

        self::assertArrayHasKey('fenAfter', $result);
        self::assertArrayHasKey('san', $result);
        self::assertNotEmpty($result['fenAfter']);
        self::assertNotEmpty($result['san']);

        // Ce coup DEVRAIT contenir une promotion
        self::assertStringContainsString(
            '=',
            $result['san'],
            'Le coup e7e8q devrait contenir une promotion'
        );
    }

    public function testInvalidUciThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid_uci');

        $this->engine->applyUci('startpos', 'invalid');
    }

    public function testInvalidFenThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid_fen');

        $this->engine->applyUci('invalid_fen', 'e2e4');
    }

    public function testIllegalMoveThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('illegal_move');

        // Coup impossible: de la case e2 vers la même case e2
        $this->engine->applyUci('startpos', 'e2e2');
    }
}
