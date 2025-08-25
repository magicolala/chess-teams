<?php

namespace App\Application\DTO;

final class JoinByCodeInput
{
    public function __construct(
        public readonly string $inviteCode,
        public readonly string $userId,
    ) {
    }
}
