<?php

namespace App\Tests\Unit;

use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\InviteRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ContainerBindingsTest extends KernelTestCase
{
    public function testRepositoryInterfacesAreBound(): void
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
