<?php

namespace App\Tests\Unit\Application\DTO;

use App\Application\DTO\ClaimVictoryOutput;
use PHPUnit\Framework\TestCase;

final class ClaimVictoryOutputTest extends TestCase
{
    public function testConstructAndProperties(): void
    {
        $dto = new ClaimVictoryOutput('g1', true, 'B+Atimeout', 'finished', 'B');
        self::assertSame('g1', $dto->gameId);
        self::assertTrue($dto->claimed);
        self::assertSame('B+Atimeout', $dto->result);
        self::assertSame('finished', $dto->status);
        self::assertSame('B', $dto->winnerTeam);
    }
}
