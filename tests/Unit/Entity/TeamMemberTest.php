<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Game;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class TeamMemberTest extends TestCase
{
    public function testDefaultsAndSetters(): void
    {
        $user = new User();
        $user->setEmail('u@test.io');
        $user->setPassword('x');

        $g = new Game();
        $team = new Team($g, Team::NAME_A);

        $m = new TeamMember($team, $user, 2);

        self::assertSame($team, $m->getTeam());
        self::assertSame($g, $m->getGame());
        self::assertSame($user, $m->getUser());
        self::assertSame(2, $m->getPosition());
        self::assertTrue($m->isActive());
        self::assertFalse($m->isReadyToStart());

        $m->setPosition(5);
        $m->setActive(false);
        $m->setReadyToStart(true);

        self::assertSame(5, $m->getPosition());
        self::assertFalse($m->isActive());
        self::assertTrue($m->isReadyToStart());
    }
}
