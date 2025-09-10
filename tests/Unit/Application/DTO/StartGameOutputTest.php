<?php

namespace App\Tests\Unit\Application\DTO;

use App\Application\DTO\StartGameOutput;
use PHPUnit\Framework\TestCase;

final class StartGameOutputTest extends TestCase
{
    public function testConstructAndProperties(): void
    {
        $dto = new StartGameOutput('g1', 'live', 'A', 1234567890);
        self::assertSame('g1', $dto->gameId);
        self::assertSame('live', $dto->status);
        self::assertSame('A', $dto->turnTeam);
        self::assertSame(1234567890, $dto->turnDeadlineTs);
    }
}
