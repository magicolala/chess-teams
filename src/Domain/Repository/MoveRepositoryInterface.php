<?php

namespace App\Domain\Repository;

use App\Entity\Game;
use App\Entity\Move;

interface MoveRepositoryInterface
{
    public function add(Move $move): void;

    public function countByGame(Game $game): int;

    public function lastPlyByGame(Game $game): int; // -1 si aucun

    /** @return Move[] */
    public function listByGameOrdered(Game $game): array;

    /**
     * Retourne les coups strictement après le ply fourni (exclusif), ordonnés par ply croissant.
     *
     * @return Move[]
     */
    public function listByGameSince(Game $game, int $sincePly): array;
}
