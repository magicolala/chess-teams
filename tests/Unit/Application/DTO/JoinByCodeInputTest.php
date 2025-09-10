<?php

namespace App\Tests\Unit\Application\DTO;

use App\Application\DTO\JoinByCodeInput;
use PHPUnit\Framework\TestCase;

final class JoinByCodeInputTest extends TestCase
{
    public function testConstructAndProperties(): void
    {
        $dto = new JoinByCodeInput('INV123', 'u1');
        self::assertSame('INV123', $dto->inviteCode);
        self::assertSame('u1', $dto->userId);
    }
}
