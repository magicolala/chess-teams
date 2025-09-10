<?php

namespace App\Tests\Unit\Application\DTO;

use App\Application\DTO\TimeoutTickInput;
use PHPUnit\Framework\TestCase;

final class TimeoutTickInputTest extends TestCase
{
    public function testConstructAndProperties(): void
    {
        $dto = new TimeoutTickInput('g1', 'u1');
        self::assertSame('g1', $dto->gameId);
        self::assertSame('u1', $dto->requestedByUserId);
    }
}
