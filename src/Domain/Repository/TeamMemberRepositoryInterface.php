<?php

namespace App\Domain\Repository;

use App\Entity\Game;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;

interface TeamMemberRepositoryInterface
{
    public function add(TeamMember $member): void;

    public function countActiveByTeam(Team $team): int;

    public function maxPositionByTeam(Team $team): int; // -1 si aucun

    public function findOneByTeamAndUser(Team $team, User $user): ?TeamMember;

    public function findOneByGameAndUser(Game $game, User $user): ?TeamMember;

    public function findActiveOrderedByTeam(Team $team): array; // TeamMember[]
}
