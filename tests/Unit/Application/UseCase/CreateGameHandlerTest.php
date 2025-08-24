<?php

namespace App\Tests\Unit\Application\UseCase;

use App\Application\DTO\CreateGameInput;
use App\Application\UseCase\CreateGameHandler;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\InviteRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class CreateGameHandlerTest extends TestCase
{
    public function testCreatesGameTeamsInviteAndReturnsOutput(): void
    {
        $games = $this->createMock(GameRepositoryInterface::class);
        $teams = $this->createMock(TeamRepositoryInterface::class);
        $invites = $this->createMock(InviteRepositoryInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $games->expects($this->once())->method('add');
        $teams->expects($this->exactly(2))->method('add');
        $invites->expects($this->once())->method('add');
        $em->expects($this->once())->method('flush');

        $handler = new CreateGameHandler($games, $teams, $invites, $em);

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashed');

        $in = new CreateGameInput($user->getId() ?? 'x', 60, 'private');

        $out = $handler($in, $user);

        $this->assertNotEmpty($out->gameId);
        $this->assertNotEmpty($out->inviteCode);
        $this->assertSame(60, $out->turnDurationSec);
    }
}
