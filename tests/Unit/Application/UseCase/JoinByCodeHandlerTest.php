<?php
namespace App\Tests\Unit\Application\UseCase;

use App\Application\DTO\JoinByCodeInput;
use App\Application\UseCase\JoinByCodeHandler;
use App\Domain\Repository\{InviteRepositoryInterface, TeamRepositoryInterface, TeamMemberRepositoryInterface};
use App\Entity\{Game, Team, Invite, User, TeamMember};
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class JoinByCodeHandlerTest extends TestCase
{
    public function test_join_assigns_to_smaller_team_and_returns_position(): void
    {
        $invRepo = $this->createMock(InviteRepositoryInterface::class);
        $teamRepo = $this->createMock(TeamRepositoryInterface::class);
        $memRepo = $this->createMock(TeamMemberRepositoryInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $handler = new JoinByCodeHandler($invRepo, $teamRepo, $memRepo, $em);

        $game = (new Game())->setStatus(Game::STATUS_LOBBY);
        $teamA = new Team($game, Team::NAME_A);
        $teamB = new Team($game, Team::NAME_B);

        $invite = new Invite($game, 'ABCD1234');

        $invRepo->method('findOneByCode')->with('ABCD1234')->willReturn($invite);
        $teamRepo->method('findOneByGameAndName')->willReturnMap([
            [$game, Team::NAME_A, $teamA],
            [$game, Team::NAME_B, $teamB],
        ]);

        // A a 1 joueur, B a 0 → B choisi
        $memRepo->method('countActiveByTeam')->willReturnMap([
            [$teamA, 1],
            [$teamB, 0],
        ]);
        $memRepo->method('findOneByTeamAndUser')->willReturn(null);
        $memRepo->method('maxPositionByTeam')->with($teamB)->willReturn(-1);

        $memRepo->expects($this->once())->method('add');
        $em->expects($this->once())->method('flush');

        $user = new User();
        $user->setEmail('u@test.io'); $user->setPassword('x');

        $out = $handler(new JoinByCodeInput('ABCD1234', 'uid'), $user);
        $this->assertSame('B', $out->teamName);
        $this->assertSame(0, $out->position);
    }
}
