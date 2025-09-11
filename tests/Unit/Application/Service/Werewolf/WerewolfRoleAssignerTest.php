<?php

namespace App\Tests\Unit\Application\Service\Werewolf;

use App\Application\Service\Werewolf\WerewolfRoleAssigner;
use App\Entity\Game;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 * @covers \App\Application\Service\Werewolf\WerewolfRoleAssigner
 */
final class WerewolfRoleAssignerTest extends KernelTestCase
{
    public function testAssignSingleWerewolfWhenEnoughPlayers(): void
    {
        self::bootKernel();
        $c = self::getContainer();
        $em = $c->get('doctrine')->getManager();
        \assert($em instanceof EntityManagerInterface);

        $g = new Game();
        $g->setMode('werewolf')->setTwoWolvesEnabled(false);
        $em->persist($g);

        $users = [];
        for ($i = 0; $i < 4; ++$i) {
            $u = new User();
            $u->setEmail('u'.$i.'@t.io');
            $u->setPassword('x');
            $em->persist($u);
            $users[] = $u;
        }
        $em->flush();

        /** @var WerewolfRoleAssigner $assigner */
        $assigner = $c->get(WerewolfRoleAssigner::class);
        $roles = $assigner->assignForGame($g, [$users[0], $users[1]], [$users[2], $users[3]]);

        self::assertNotEmpty($roles);
        $werewolves = array_filter($roles, static fn($r) => $r->getRole() === 'werewolf');
        self::assertCount(1, $werewolves, 'Should assign exactly one werewolf.');
    }

    public function testAssignTwoWerewolvesWhenOptionEnabled(): void
    {
        self::bootKernel();
        $c = self::getContainer();
        $em = $c->get('doctrine')->getManager();

        $g = new Game();
        $g->setMode('werewolf')->setTwoWolvesEnabled(true);
        $em->persist($g);

        $a = [];$b = [];
        for ($i = 0; $i < 2; ++$i) {
            $u = new User();
            $u->setEmail('A'.$i.'@t.io');
            $u->setPassword('x');
            $em->persist($u);
            $a[] = $u;
        }
        for ($i = 0; $i < 2; ++$i) {
            $u = new User();
            $u->setEmail('B'.$i.'@t.io');
            $u->setPassword('x');
            $em->persist($u);
            $b[] = $u;
        }
        $em->flush();

        /** @var WerewolfRoleAssigner $assigner */
        $assigner = $c->get(WerewolfRoleAssigner::class);
        $roles = $assigner->assignForGame($g, $a, $b);

        $wolves = array_filter($roles, static fn($r) => $r->getRole() === 'werewolf');
        self::assertCount(2, $wolves, 'Should assign one werewolf per team.');
    }
}
