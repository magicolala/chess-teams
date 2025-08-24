<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class TeamMemberRepository extends ServiceEntityRepository implements TeamMemberRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamMember::class);
    }

    public function add(TeamMember $member): void
    {
        $this->getEntityManager()->persist($member);
    }

    public function countActiveByTeam(Team $team): int
    {
        return (int) $this->count(['team' => $team, 'active' => true]);
    }

    public function maxPositionByTeam(Team $team): int
    {
        $q = $this->createQueryBuilder('m')
            ->select('MAX(m.position)')
            ->where('m.team = :t')->setParameter('t', $team)
            ->getQuery()->getSingleScalarResult();

        return null === $q ? -1 : (int) $q;
    }

    public function findOneByTeamAndUser(Team $team, User $user): ?TeamMember
    {
        return $this->findOneBy(['team' => $team, 'user' => $user]);
    }
}
