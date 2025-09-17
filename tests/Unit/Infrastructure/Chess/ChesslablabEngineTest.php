<?php

namespace App\Tests\Unit\Infrastructure\Chess;

use App\Infrastructure\Chess\ChesslablabEngine;
use PHPUnit\Framework\TestCase;

final class ChesslablabEngineTest extends TestCase
{
    public function testApplyUciFromStartposReturnsFenAndSan(): void
    {
        $engine = new ChesslablabEngine();
        $out = $engine->applyUci('startpos', 'e2e4');

        self::assertArrayHasKey('fenAfter', $out);
        self::assertArrayHasKey('san', $out);
        self::assertNotSame('', (string) $out['fenAfter']);
        self::assertNotSame('', (string) $out['san']);
    }

    public function testApplyUciRejectsInvalidUci(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $engine = new ChesslablabEngine();
        $engine->applyUci('startpos', 'invalid');
    }
}
