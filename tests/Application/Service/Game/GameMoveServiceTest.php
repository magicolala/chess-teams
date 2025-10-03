<?php

namespace App\Tests\Application\Service\Game;

use App\Application\DTO\MakeMoveInput;
use App\Application\Port\ChessEngineInterface;
use App\Application\Service\Game\GameMoveService;
use App\Application\Service\GameEndEvaluator;
use App\Application\Service\Werewolf\WerewolfVoteService;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\MoveRepositoryInterface;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\Move;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

final class GameMoveServiceTest extends TestCase
{
    public function testInvalidUciIsRejected(): void
    {
        $game = $this->createLiveGame();
        $teamA = new Team($game, Team::NAME_A);
        $teamB = new Team($game, Team::NAME_B);
        $player = $this->createUser();
        $member = new TeamMember($teamA, $player, 0);

        $games = $this->createMock(GameRepositoryInterface::class);
        $games->method('get')->with($game->getId())->willReturn($game);

        $teams = $this->createMock(TeamRepositoryInterface::class);
        $teams->method('findOneByGameAndName')->willReturnMap([
            [$game, Team::NAME_A, $teamA],
            [$game, Team::NAME_B, $teamB],
        ]);

        $members = $this->createMock(TeamMemberRepositoryInterface::class);
        $members->method('findActiveOrderedByTeam')->with($teamA)->willReturn([$member]);

        $moves = $this->createMock(MoveRepositoryInterface::class);
        $moves->expects($this->never())->method('add');

        $engine = $this->createMock(ChessEngineInterface::class);
        $engine->expects($this->never())->method('applyUci');

        $lock = $this->createConfiguredMock(LockInterface::class, [
            'acquire' => true,
        ]);
        $lock->expects($this->once())->method('release');

        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->method('createLock')->willReturn($lock);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $werewolf = $this->createMock(WerewolfVoteService::class);

        $service = new GameMoveService(
            $games,
            $teams,
            $members,
            $moves,
            $engine,
            $lockFactory,
            $em,
            new GameEndEvaluator(),
            $werewolf,
        );

        $input = new MakeMoveInput($game->getId(), 'bad', (string) $player->getId());

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('invalid_uci');

        $service->play($input, $player);
    }

    public function testSanFallsBackToUciWhenEngineReturnsEmpty(): void
    {
        $game = $this->createLiveGame();
        $teamA = new Team($game, Team::NAME_A);
        $teamB = new Team($game, Team::NAME_B);
        $player = $this->createUser();
        $member = new TeamMember($teamA, $player, 0);

        $games = $this->createMock(GameRepositoryInterface::class);
        $games->method('get')->with($game->getId())->willReturn($game);

        $teams = $this->createMock(TeamRepositoryInterface::class);
        $teams->method('findOneByGameAndName')->willReturnMap([
            [$game, Team::NAME_A, $teamA],
            [$game, Team::NAME_B, $teamB],
        ]);

        $members = $this->createMock(TeamMemberRepositoryInterface::class);
        $members->method('findActiveOrderedByTeam')->with($teamA)->willReturn([$member]);

        $moves = $this->createMock(MoveRepositoryInterface::class);
        $moves->expects($this->once())
            ->method('add')
            ->with($this->callback(function (Move $move): bool {
                return 'e2e4' === $move->getSan() && 'e2e4' === $move->getUci();
            }));

        $engine = $this->createMock(ChessEngineInterface::class);
        $engine->method('applyUci')->willReturn([
            'fenAfter' => 'fen-after',
            'san' => '',
        ]);

        $lock = $this->createConfiguredMock(LockInterface::class, [
            'acquire' => true,
        ]);
        $lock->expects($this->once())->method('release');
        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->method('createLock')->willReturn($lock);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $werewolf = $this->createMock(WerewolfVoteService::class);
        $werewolf->expects($this->never())->method('openVote');

        $service = new GameMoveService(
            $games,
            $teams,
            $members,
            $moves,
            $engine,
            $lockFactory,
            $em,
            new GameEndEvaluator(),
            $werewolf,
        );

        $input = new MakeMoveInput($game->getId(), 'e2e4', (string) $player->getId());
        $result = $service->play($input, $player);

        $this->assertSame(1, $result->ply);
        $this->assertSame('fen-after', $game->getFen());
        $this->assertSame(Game::TEAM_B, $game->getTurnTeam());
        $this->assertNotNull($game->getTurnDeadline());
    }

    private function createLiveGame(): Game
    {
        $game = new Game();
        $game->setStatus(Game::STATUS_LIVE);
        $game->setTurnTeam(Game::TEAM_A);
        $game->setFen('startpos');

        return $game;
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('user@example.com');

        return $user;
    }
}
