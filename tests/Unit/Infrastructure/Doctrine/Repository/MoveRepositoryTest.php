<?php

namespace App\Tests\Unit\Infrastructure\Doctrine\Repository;

use App\Entity\Move;
use App\Infrastructure\Doctrine\Repository\MoveRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class MoveRepositoryTest extends TestCase
{
    /**
     * @param EntityManagerInterface&MockObject $em
     */
    private function createRepository(EntityManagerInterface $em): MoveRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->name = Move::class;
        $em->method('getClassMetadata')->with(Move::class)->willReturn($classMetadata);

        return new MoveRepository($registry);
    }

    public function testAddPersistsEntity(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createRepository($em);

        $em->expects(self::once())->method('persist')->with(self::isInstanceOf(Move::class));

        $repo->add(new Move(new \App\Entity\Game(), 1));
    }
}
