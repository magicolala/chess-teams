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

/**
 * @internal
 *
 * @coversNothing
 */
final class CreateGameHandlerTest extends TestCase
{
    public function testCreatesGameTeamsInviteAndReturnsOutput(): void
    {
        $games   = $this->createMock(GameRepositoryInterface::class);
        $teams   = $this->createMock(TeamRepositoryInterface::class);
        $invites = $this->createMock(InviteRepositoryInterface::class);
        $em      = $this->createMock(EntityManagerInterface::class);

        $games->expects(self::once())->method('add');
        $teams->expects(self::exactly(2))->method('add');
        $invites->expects(self::once())->method('add');
        $em->expects(self::once())->method('flush');

        $handler = new CreateGameHandler($games, $teams, $invites, $em);

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashed');

        $in = new CreateGameInput($user->getId() ?? 'x', 60, 'private');

        $out = $handler($in, $user);

        self::assertNotEmpty($out->gameId);
        self::assertNotEmpty($out->inviteCode);
        self::assertSame(60, $out->turnDurationSec);
    }
}
