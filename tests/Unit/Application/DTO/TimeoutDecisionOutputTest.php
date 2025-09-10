<?php

namespace App\Tests\Unit\Application\DTO;

use App\Application\DTO\TimeoutDecisionOutput;
use PHPUnit\Framework\TestCase;

final class TimeoutDecisionOutputTest extends TestCase
{
    public function testConstructAndProperties(): void
    {
        $dto = new TimeoutDecisionOutput('g1', 'live', null, false, 'A', 1234);
        self::assertSame('g1', $dto->gameId);
        self::assertSame('live', $dto->status);
        self::assertNull($dto->result);
        self::assertFalse($dto->pending);
        self::assertSame('A', $dto->turnTeam);
        self::assertSame(1234, $dto->turnDeadlineTs);
    }
}
