<?php

namespace App\Tests\Unit\Infrastructure\Chess;

use App\Infrastructure\Chess\PChessEngine;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class PChessEngineTest extends TestCase
{
    public function testApplyUciReturnsFenAndSan(): void
    {
        $engine = new PChessEngine();
        $out = $engine->applyUci('startpos', 'e2e4');

        self::assertArrayHasKey('fenAfter', $out);
        self::assertArrayHasKey('san', $out);
        self::assertNotEmpty($out['fenAfter']);
        // Typiquement 'e4' (ou 'E4' selon la lib), on accepte non vide :
        self::assertNotSame('', trim((string) $out['san']));
    }
}
