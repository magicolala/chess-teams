<?php

namespace App\Tests\Application\UseCase;

use App\Application\DTO\CreateGameInput;
use App\Application\UseCase\CreateGameHandler;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\InviteRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\Invite;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreateGameHandlerTest extends TestCase
{
    private CreateGameHandler $handler;
    private GameRepositoryInterface $gameRepo;
    private TeamRepositoryInterface $teamRepo;
    private InviteRepositoryInterface $inviteRepo;
    private EntityManagerInterface $em;
    private User $user;

    protected function setUp(): void
    {
        $this->gameRepo = $this->createMock(GameRepositoryInterface::class);
        $this->teamRepo = $this->createMock(TeamRepositoryInterface::class);
        $this->inviteRepo = $this->createMock(InviteRepositoryInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->handler = new CreateGameHandler(
            $this->gameRepo,
            $this->teamRepo,
            $this->inviteRepo,
            $this->em
        );

        $this->user = $this->createMock(User::class);
        $this->user->method('getId')->willReturn('user-123');
    }

    public function testCreateGame(): void
    {
        $input = new CreateGameInput(
            creatorUserId: 'user-123',
            turnDurationSec: 120,
            visibility: 'public'
        );

        // Expect the repositories to be called
        $this->gameRepo->expects($this->once())->method('add');
        $this->teamRepo->expects($this->exactly(2))->method('add'); // Team A and Team B
        $this->inviteRepo->expects($this->once())->method('add');
        $this->em->expects($this->once())->method('flush');

        $output = ($this->handler)($input, $this->user);

        $this->assertNotNull($output->gameId);
        $this->assertNotNull($output->inviteCode);
        $this->assertEquals(120, $output->turnDurationSec);
    }

    public function testCreateGameWithMinimumTurnDuration(): void
    {
        $input = new CreateGameInput(
            creatorUserId: 'user-123',
            turnDurationSec: 5, // Below minimum
            visibility: 'private'
        );

        $this->gameRepo->expects($this->once())->method('add');
        $this->teamRepo->expects($this->exactly(2))->method('add');
        $this->inviteRepo->expects($this->once())->method('add');
        $this->em->expects($this->once())->method('flush');

        $output = ($this->handler)($input, $this->user);

        // Turn duration should be clamped to minimum (10)
        $this->assertEquals(10, $output->turnDurationSec);
    }

    public function testCreateGameDefaultValues(): void
    {
        $input = new CreateGameInput(
            creatorUserId: 'user-123',
            turnDurationSec: 60,
            visibility: 'private'
        );

        // Capture the game that gets added
        $capturedGame = null;
        $this->gameRepo->expects($this->once())
            ->method('add')
            ->willReturnCallback(function (Game $game) use (&$capturedGame) {
                $capturedGame = $game;
            });

        $this->teamRepo->expects($this->exactly(2))->method('add');
        $this->inviteRepo->expects($this->once())->method('add');
        $this->em->expects($this->once())->method('flush');

        ($this->handler)($input, $this->user);

        // Verify game properties
        $this->assertEquals($this->user, $capturedGame->getCreatedBy());
        $this->assertEquals(60, $capturedGame->getTurnDurationSec());
        $this->assertEquals('private', $capturedGame->getVisibility());
        $this->assertEquals('startpos', $capturedGame->getFen());
        $this->assertEquals(0, $capturedGame->getPly());
        $this->assertEquals(Game::TEAM_A, $capturedGame->getTurnTeam());
    }

    public function testInviteCodeGeneration(): void
    {
        $input = new CreateGameInput(
            creatorUserId: 'user-123',
            turnDurationSec: 60,
            visibility: 'private'
        );

        $this->gameRepo->expects($this->once())->method('add');
        $this->teamRepo->expects($this->exactly(2))->method('add');
        $this->inviteRepo->expects($this->once())->method('add');
        $this->em->expects($this->once())->method('flush');

        $output = ($this->handler)($input, $this->user);

        // Invite code should be a string of 12 characters
        $this->assertEquals(12, strlen($output->inviteCode));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{12}$/', $output->inviteCode);
    }
}
