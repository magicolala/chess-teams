<?php

namespace App\Tests\Unit\Infrastructure\Doctrine\Repository;

use App\Entity\Game;
use App\Infrastructure\Doctrine\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;

final class GameRepositoryTest extends TestCase
{
    private function createRepository(EntityManagerInterface $em): GameRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->name = Game::class;
        $em->method('getClassMetadata')->with(Game::class)->willReturn($classMetadata);

        return new GameRepository($registry);
    }

    public function testAddPersistsEntity(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createRepository($em);

        $em->expects(self::once())->method('persist')->with(self::isInstanceOf(Game::class));

        $repo->add(new Game());
    }

    public function testGetReturnsNullForInvalidUuid(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createRepository($em);

        $result = $repo->get('not-a-uuid');
        self::assertNull($result);
    }
}

