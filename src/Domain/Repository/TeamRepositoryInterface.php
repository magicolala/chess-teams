<?php

namespace App\Domain\Repository;

use App\Entity\Game;
use App\Entity\Team;

interface TeamRepositoryInterface
{
    public function add(Team $team): void;

    public function findOneByGameAndName(Game $game, string $name): ?Team;
}
