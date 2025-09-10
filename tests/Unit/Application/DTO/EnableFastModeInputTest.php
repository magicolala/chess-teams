<?php

namespace App\Tests\Unit\Application\DTO;

use App\Application\DTO\EnableFastModeInput;
use PHPUnit\Framework\TestCase;

final class EnableFastModeInputTest extends TestCase
{
    public function testConstructAndProperties(): void
    {
        $dto = new EnableFastModeInput('g1', 'u1');
        self::assertSame('g1', $dto->gameId);
        self::assertSame('u1', $dto->userId);
    }
}
