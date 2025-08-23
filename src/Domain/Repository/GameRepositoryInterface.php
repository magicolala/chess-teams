<?php
namespace App\Domain\Repository;

use App\Entity\Game;

interface GameRepositoryInterface
{
    public function add(Game $game): void;
    public function get(string $id): ?Game;
}
