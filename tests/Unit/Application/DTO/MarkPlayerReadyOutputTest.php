<?php

namespace App\Tests\Unit\Application\DTO;

use App\Application\DTO\MarkPlayerReadyOutput;
use PHPUnit\Framework\TestCase;

final class MarkPlayerReadyOutputTest extends TestCase
{
    public function testConstructAndProperties(): void
    {
        $dto = new MarkPlayerReadyOutput('g1', 'u1', true, true, 2, 2);
        self::assertSame('g1', $dto->gameId);
        self::assertSame('u1', $dto->userId);
        self::assertTrue($dto->ready);
        self::assertTrue($dto->allPlayersReady);
        self::assertSame(2, $dto->readyPlayersCount);
        self::assertSame(2, $dto->totalPlayersCount);
    }
}
