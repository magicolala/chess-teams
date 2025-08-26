<?php

namespace App\Tests\Unit;

use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\InviteRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ContainerBindingsTest extends KernelTestCase
{
    public function testRepositoryInterfacesAreBound(): void
    {
        self::bootKernel();
        $c = self::getContainer();

        self::assertInstanceOf(
            \App\Infrastructure\Doctrine\Repository\GameRepository::class,
            $c->get(GameRepositoryInterface::class)
        );
        self::assertInstanceOf(
            \App\Infrastructure\Doctrine\Repository\TeamRepository::class,
            $c->get(TeamRepositoryInterface::class)
        );
        self::assertInstanceOf(
            \App\Infrastructure\Doctrine\Repository\InviteRepository::class,
            $c->get(InviteRepositoryInterface::class)
        );
    }
}
