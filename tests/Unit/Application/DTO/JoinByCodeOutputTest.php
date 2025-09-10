<?php

namespace App\Tests\Unit\Application\DTO;

use App\Application\DTO\JoinByCodeOutput;
use PHPUnit\Framework\TestCase;

final class JoinByCodeOutputTest extends TestCase
{
    public function testConstructAndProperties(): void
    {
        $dto = new JoinByCodeOutput('A', 3);
        self::assertSame('A', $dto->teamName);
        self::assertSame(3, $dto->position);
    }
}
