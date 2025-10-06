<?php

namespace App\Tests\Unit\Application\DTO;

use App\Application\DTO\TimeoutTickOutput;
use PHPUnit\Framework\TestCase;

final class TimeoutTickOutputTest extends TestCase
{
    public function testConstructAndProperties(): void
    {
        $dto = new TimeoutTickOutput('g1', true, 42, 'B', 999, 'fen-str', 'brain', 'knight', 'brain-id', 'hand-id');
        self::assertSame('g1', $dto->gameId);
        self::assertTrue($dto->timedOutApplied);
        self::assertSame(42, $dto->ply);
        self::assertSame('B', $dto->turnTeam);
        self::assertSame(999, $dto->turnDeadlineTs);
        self::assertSame('fen-str', $dto->fen);
        self::assertSame('brain', $dto->handBrainCurrentRole);
        self::assertSame('knight', $dto->handBrainPieceHint);
        self::assertSame('brain-id', $dto->handBrainBrainMemberId);
        self::assertSame('hand-id', $dto->handBrainHandMemberId);
    }
}
