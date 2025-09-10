<?php

namespace App\Tests\Unit\Application\DTO;

use App\Application\DTO\StartGameInput;
use PHPUnit\Framework\TestCase;

final class StartGameInputTest extends TestCase
{
    public function testConstructAndProperties(): void
    {
        $dto = new StartGameInput('g1', 'u1');
        self::assertSame('g1', $dto->gameId);
        self::assertSame('u1', $dto->requestedByUserId);
    }
}
