<?php
namespace App\Tests\Unit;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Domain\Repository\{
    GameRepositoryInterface,
    TeamRepositoryInterface,
    InviteRepositoryInterface
};

final class ContainerBindingsTest extends KernelTestCase
{
    public function test_repository_interfaces_are_bound(): void
    {
        self::bootKernel();
        $c = static::getContainer();

        $this->assertInstanceOf(
            \App\Infrastructure\Doctrine\Repository\GameRepository::class,
            $c->get(GameRepositoryInterface::class)
        );
        $this->assertInstanceOf(
            \App\Infrastructure\Doctrine\Repository\TeamRepository::class,
            $c->get(TeamRepositoryInterface::class)
        );
        $this->assertInstanceOf(
            \App\Infrastructure\Doctrine\Repository\InviteRepository::class,
            $c->get(InviteRepositoryInterface::class)
        );
    }
}
