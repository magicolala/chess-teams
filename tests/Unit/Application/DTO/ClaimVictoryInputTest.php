<?php

namespace App\Tests\Unit\Application\DTO;

use App\Application\DTO\ClaimVictoryInput;
use PHPUnit\Framework\TestCase;

final class ClaimVictoryInputTest extends TestCase
{
    public function testConstructAndProperties(): void
    {
        $dto = new ClaimVictoryInput('game-1', 'user-1');
        self::assertSame('game-1', $dto->gameId);
        self::assertSame('user-1', $dto->userId);
    }
}
