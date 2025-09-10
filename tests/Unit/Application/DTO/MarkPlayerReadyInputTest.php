<?php

namespace App\Tests\Unit\Application\DTO;

use App\Application\DTO\MarkPlayerReadyInput;
use PHPUnit\Framework\TestCase;

final class MarkPlayerReadyInputTest extends TestCase
{
    public function testConstructAndDefaults(): void
    {
        $dto = new MarkPlayerReadyInput('g1', 'u1');
        self::assertSame('g1', $dto->gameId);
        self::assertSame('u1', $dto->userId);
        self::assertTrue($dto->ready, 'Default ready should be true');
    }

    public function testConstructWithExplicitReady(): void
    {
        $dto = new MarkPlayerReadyInput('g2', 'u2', false);
        self::assertFalse($dto->ready);
    }
}
