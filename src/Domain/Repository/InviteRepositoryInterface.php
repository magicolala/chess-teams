<?php
namespace App\Domain\Repository;

use App\Entity\{Game, Invite};

interface InviteRepositoryInterface
{
    public function add(Invite $invite): void;
    public function findOneByGame(Game $game): ?Invite;
    public function findOneByCode(string $code): ?Invite;
}
