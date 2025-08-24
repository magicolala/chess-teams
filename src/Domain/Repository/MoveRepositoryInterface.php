<?php

namespace App\Domain\Repository;

use App\Entity\Game;
use App\Entity\Move;

interface MoveRepositoryInterface
{
    public function add(Move $move): void;

    public function countByGame(Game $game): int;

    public function lastPlyByGame(Game $game): int; // -1 si aucun
}
