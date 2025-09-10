<?php

namespace App\Tests\Unit\Application\DTO;

use App\Application\DTO\TimeoutDecisionInput;
use PHPUnit\Framework\TestCase;

final class TimeoutDecisionInputTest extends TestCase
{
    public function testConstructAndProperties(): void
    {
        $dto = new TimeoutDecisionInput('g1', 'u1', 'end');
        self::assertSame('g1', $dto->gameId);
        self::assertSame('u1', $dto->userId);
        self::assertSame('end', $dto->decision);
    }
}
