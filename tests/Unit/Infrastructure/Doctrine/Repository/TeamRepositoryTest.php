<?php

namespace App\Tests\Unit\Infrastructure\Doctrine\Repository;

use App\Entity\Team;
use App\Infrastructure\Doctrine\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TeamRepositoryTest extends TestCase
{
    /**
     * @param EntityManagerInterface&MockObject $em
     */
    private function createRepository(EntityManagerInterface $em): TeamRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->name = Team::class;
        $em->method('getClassMetadata')->with(Team::class)->willReturn($classMetadata);

        return new TeamRepository($registry);
    }

    public function testAddPersistsEntity(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createRepository($em);

        $em->expects(self::once())->method('persist')->with(self::isInstanceOf(Team::class));

        $game = new \App\Entity\Game();
        $repo->add(new Team($game, Team::NAME_A));
    }
}
