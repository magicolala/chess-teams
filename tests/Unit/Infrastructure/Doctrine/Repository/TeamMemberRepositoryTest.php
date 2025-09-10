<?php

namespace App\Tests\Unit\Infrastructure\Doctrine\Repository;

use App\Entity\TeamMember;
use App\Infrastructure\Doctrine\Repository\TeamMemberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class TeamMemberRepositoryTest extends TestCase
{
    private function createRepository(EntityManagerInterface $em): TeamMemberRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->name = TeamMember::class;
        $em->method('getClassMetadata')->with(TeamMember::class)->willReturn($classMetadata);

        return new TeamMemberRepository($registry);
    }

    public function testAddPersistsEntity(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createRepository($em);

        $em->expects(self::once())->method('persist')->with(self::isInstanceOf(TeamMember::class));

        // minimal valid constructor arguments for TeamMember
        $game = new \App\Entity\Game();
        $team = new \App\Entity\Team($game, \App\Entity\Team::NAME_A);
        $user = new \App\Entity\User();
        $member = new TeamMember($team, $user, 0);

        $repo->add($member);
    }
}
