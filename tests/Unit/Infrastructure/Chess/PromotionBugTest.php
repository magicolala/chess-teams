<?php

namespace App\Tests\Unit\Infrastructure\Chess;

use App\Infrastructure\Chess\PChessEngine;
use PHPUnit\Framework\TestCase;

/**
 * Tests spécifiques pour s'assurer que le bug de promotion est corrigé.
 * Le bug : le paramètre "promotion" était ajouté à tous les coups, même ceux qui n'en avaient pas besoin.
 * 
 * @internal
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
        $normalMoves = [
            'e2e4',  // Pion deux cases
            'd2d3',  // Pion une case
            'g1f3',  // Cavalier
            'f1e2',  // Fou
            'h1g1',  // Tour
            'd1e2',  // Dame
            'e1f1'   // Roi
        ];

        foreach ($normalMoves as $uci) {
            $result = $this->engine->applyUci('startpos', $uci);
            
            self::assertArrayHasKey('fenAfter', $result);
            self::assertArrayHasKey('san', $result);
            self::assertNotEmpty($result['fenAfter']);
            self::assertNotEmpty($result['san']);
            
            // Aucun de ces coups ne devrait contenir de promotion
            self::assertStringNotContainsString('=', $result['san'], 
                "Le coup {$uci} ne devrait pas contenir de promotion");
        }
    }

    public function testPromotionMoveIncludesPromotion(): void
    {
        // e7-e8q est un coup de promotion (pion blanc de la 7e à la 8e rangée)
        // Note: pour ce test, nous utilisons une FEN où un pion blanc peut être promu
        $fenWithPawnOnSeventh = 'rnbqkbnr/ppppPppp/8/8/8/8/PPPP1PPP/RNBQKBNR w KQkq - 0 1';
        
        $result = $this->engine->applyUci($fenWithPawnOnSeventh, 'e7e8q');
        
        self::assertArrayHasKey('fenAfter', $result);
        self::assertArrayHasKey('san', $result);
        self::assertNotEmpty($result['fenAfter']);
        self::assertNotEmpty($result['san']);
        
        // Ce coup DEVRAIT contenir une promotion
        self::assertStringContainsString('=', $result['san'], 
            "Le coup e7e8q devrait contenir une promotion");
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
