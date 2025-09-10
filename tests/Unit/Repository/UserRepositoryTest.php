<?php

namespace App\Tests\Unit\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class UserRepositoryTest extends TestCase
{
    public function testConstruct(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $repo = new UserRepository($registry);
        self::assertInstanceOf(UserRepository::class, $repo);
        self::assertTrue(class_exists(User::class));
    }
}
