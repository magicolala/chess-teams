<?php
namespace App\Domain\Repository;

use App\Entity\{Team, TeamMember, User};

interface TeamMemberRepositoryInterface
{
    public function add(TeamMember $member): void;

    public function countActiveByTeam(Team $team): int;

    public function maxPositionByTeam(Team $team): int; // -1 si aucun

    public function findOneByTeamAndUser(Team $team, User $user): ?TeamMember;
}