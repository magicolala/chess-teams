<?php

namespace App\Application\Service\Game;

use App\Application\DTO\TimeoutTickInput;
use App\Application\Service\Game\DTO\TimeoutResult;

interface GameTimeoutServiceInterface
{
    public function handle(TimeoutTickInput $input): TimeoutResult;
}
