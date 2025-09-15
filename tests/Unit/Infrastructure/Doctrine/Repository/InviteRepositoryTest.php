<?php

namespace App\Tests\Unit\Infrastructure\Doctrine\Repository;

use App\Entity\Invite;
use App\Infrastructure\Doctrine\Repository\InviteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class InviteRepositoryTest extends TestCase
{
    /**
     * @param EntityManagerInterface&MockObject $em
     */
    private function createRepository(EntityManagerInterface $em): InviteRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->name = Invite::class;
        $em->method('getClassMetadata')->with(Invite::class)->willReturn($classMetadata);

        return new InviteRepository($registry);
    }

    public function testAddPersistsEntity(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createRepository($em);

        $em->expects(self::once())->method('persist')->with(self::isInstanceOf(Invite::class));

        $dummy = new \App\Entity\Game();
        $invite = new Invite($dummy, 'CODE');
        $repo->add($invite);
    }
}
