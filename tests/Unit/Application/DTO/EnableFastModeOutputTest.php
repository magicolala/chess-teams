<?php

namespace App\Tests\Unit\Application\DTO;

use App\Application\DTO\EnableFastModeOutput;
use PHPUnit\Framework\TestCase;

final class EnableFastModeOutputTest extends TestCase
{
    public function testConstructAndProperties(): void
    {
        $dto = new EnableFastModeOutput('g1', true, 123456, 654321);
        self::assertSame('g1', $dto->gameId);
        self::assertTrue($dto->fastModeEnabled);
        self::assertSame(123456, $dto->fastModeDeadlineTs);
        self::assertSame(654321, $dto->turnDeadlineTs);
    }
}
