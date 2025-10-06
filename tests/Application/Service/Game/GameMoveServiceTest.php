<?php

namespace App\Tests\Application\Service\Game;

use App\Application\DTO\MakeMoveInput;
use App\Application\Port\ChessEngineInterface;
use App\Application\Service\Game\GameLifecycleService;
use App\Application\Service\Game\GameMoveService;
use App\Application\Service\Game\HandBrain\HandBrainMoveInspector;
use App\Application\Service\GameEndEvaluator;
use App\Application\Service\Werewolf\WerewolfRoleAssigner;
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
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

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

        $lock = $this->createConfiguredMock(SharedLockInterface::class, [
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
            new HandBrainMoveInspector(),
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

        $lock = $this->createConfiguredMock(SharedLockInterface::class, [
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
            new HandBrainMoveInspector(),
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

    public function testHandBrainMoveRejectedWhenHintMissing(): void
    {
        $game = $this->createLiveGame();
        $game->setMode('hand_brain');

        $teamA = new Team($game, Team::NAME_A);
        $teamB = new Team($game, Team::NAME_B);
        $player = $this->createUser();
        $member = new TeamMember($teamA, $player, 0);

        $game->setHandBrainMembers('brain-id', $member->getId());
        $game->setHandBrainCurrentRole('hand');

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

        $lock = $this->createConfiguredMock(SharedLockInterface::class, [
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
            new HandBrainMoveInspector(),
            $lockFactory,
            $em,
            new GameEndEvaluator(),
            $werewolf,
        );

        $input = new MakeMoveInput($game->getId(), 'e2e4', (string) $player->getId());

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('hand_brain_missing_hint');

        $service->play($input, $player);
    }

    public function testHandBrainMoveRejectedWhenPlayerIsNotAssignedHand(): void
    {
        $game = $this->createLiveGame();
        $game->setMode('hand_brain');

        $teamA = new Team($game, Team::NAME_A);
        $teamB = new Team($game, Team::NAME_B);
        $player = $this->createUser();
        $member = new TeamMember($teamA, $player, 0);
        $otherUser = $this->createUser();
        $otherMember = new TeamMember($teamA, $otherUser, 1);

        $game->setHandBrainMembers('brain-id', $otherMember->getId());
        $game->setHandBrainPieceHint('pawn');
        $game->setHandBrainCurrentRole('hand');

        $games = $this->createMock(GameRepositoryInterface::class);
        $games->method('get')->with($game->getId())->willReturn($game);

        $teams = $this->createMock(TeamRepositoryInterface::class);
        $teams->method('findOneByGameAndName')->willReturnMap([
            [$game, Team::NAME_A, $teamA],
            [$game, Team::NAME_B, $teamB],
        ]);

        $members = $this->createMock(TeamMemberRepositoryInterface::class);
        $members->method('findActiveOrderedByTeam')->with($teamA)->willReturn([$member, $otherMember]);

        $moves = $this->createMock(MoveRepositoryInterface::class);
        $moves->expects($this->never())->method('add');

        $engine = $this->createMock(ChessEngineInterface::class);
        $engine->expects($this->never())->method('applyUci');

        $lock = $this->createConfiguredMock(SharedLockInterface::class, [
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
            new HandBrainMoveInspector(),
            $lockFactory,
            $em,
            new GameEndEvaluator(),
            $werewolf,
        );

        $input = new MakeMoveInput($game->getId(), 'e2e4', (string) $player->getId());

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('hand_brain_not_assigned_hand');

        $service->play($input, $player);
    }

    public function testHandBrainMoveRejectedWhenPieceDoesNotMatchHint(): void
    {
        $game = $this->createLiveGame();
        $game->setMode('hand_brain');

        $teamA = new Team($game, Team::NAME_A);
        $teamB = new Team($game, Team::NAME_B);
        $player = $this->createUser();
        $member = new TeamMember($teamA, $player, 0);

        $game->setHandBrainMembers('brain-id', $member->getId());
        $game->setHandBrainPieceHint('knight');
        $game->setHandBrainCurrentRole('hand');

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

        $lock = $this->createConfiguredMock(SharedLockInterface::class, [
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
            new HandBrainMoveInspector(),
            $lockFactory,
            $em,
            new GameEndEvaluator(),
            $werewolf,
        );

        $input = new MakeMoveInput($game->getId(), 'e2e4', (string) $player->getId());

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('hand_brain_hint_mismatch');

        $service->play($input, $player);
    }

    public function testHandBrainRolesRotateAcrossTurns(): void
    {
        $creator = $this->createUser();
        $game = new Game();
        $game->setStatus(Game::STATUS_LOBBY);
        $game->setCreatedBy($creator);
        $game->setMode('hand_brain');

        $teamA = new Team($game, Team::NAME_A);
        $teamB = new Team($game, Team::NAME_B);

        $membersA = [];
        for ($i = 0; $i < 3; ++$i) {
            $user = new User();
            $user->setEmail(sprintf('a%d@example.com', $i));
            $membersA[] = new TeamMember($teamA, $user, $i);
        }

        $membersB = [];
        for ($i = 0; $i < 3; ++$i) {
            $user = new User();
            $user->setEmail(sprintf('b%d@example.com', $i));
            $membersB[] = new TeamMember($teamB, $user, $i);
        }

        $teams = $this->createMock(TeamRepositoryInterface::class);
        $teams->method('findOneByGameAndName')->willReturnMap([
            [$game, Team::NAME_A, $teamA],
            [$game, Team::NAME_B, $teamB],
        ]);

        $membersRepo = $this->createMock(TeamMemberRepositoryInterface::class);
        $membersRepo->method('countActiveByTeam')->willReturnMap([
            [$teamA, count($membersA)],
            [$teamB, count($membersB)],
        ]);
        $membersRepo->method('areAllActivePlayersReady')->with($game)->willReturn(true);
        $membersRepo->method('findActiveOrderedByTeam')->willReturnMap([
            [$teamA, $membersA],
            [$teamB, $membersB],
        ]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(4))->method('flush');

        $assigner = $this->createMock(WerewolfRoleAssigner::class);
        $assigner->expects($this->never())->method('assignForGame');

        $lifecycle = new GameLifecycleService($teams, $membersRepo, $em, $assigner);
        $lifecycle->start($game, $creator);

        $this->assertSame($membersA[0]->getId(), $game->getHandBrainHandMemberId());
        $this->assertSame($membersA[1]->getId(), $game->getHandBrainBrainMemberId());

        $gameId = $game->getId();

        $games = $this->createMock(GameRepositoryInterface::class);
        $games->expects($this->exactly(3))->method('get')->with($gameId)->willReturn($game);

        $moves = $this->createMock(MoveRepositoryInterface::class);
        $moves->expects($this->exactly(3))->method('add')->with($this->isInstanceOf(Move::class));

        $engine = $this->createMock(ChessEngineInterface::class);
        $engine->method('applyUci')->willReturnOnConsecutiveCalls(
            [
                'fenAfter' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq - 0 1',
                'san' => 'e4',
            ],
            [
                'fenAfter' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq - 0 2',
                'san' => 'e5',
            ],
            [
                'fenAfter' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq - 1 2',
                'san' => 'Nc3',
            ],
        );

        $lock = $this->createMock(SharedLockInterface::class);
        $lock->method('acquire')->willReturn(true);
        $lock->expects($this->exactly(3))->method('release');
        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->expects($this->exactly(3))->method('createLock')->willReturn($lock);

        $werewolf = $this->createMock(WerewolfVoteService::class);
        $werewolf->expects($this->never())->method('openVote');

        $service = new GameMoveService(
            $games,
            $teams,
            $membersRepo,
            $moves,
            $engine,
            new HandBrainMoveInspector(),
            $lockFactory,
            $em,
            new GameEndEvaluator(),
            $werewolf,
        );

        $game->setHandBrainPieceHint('pawn');
        $game->setHandBrainCurrentRole('hand');
        $service->play(new MakeMoveInput($gameId, 'e2e4', (string) $membersA[0]->getUser()->getId()), $membersA[0]->getUser());
        $this->assertSame($membersB[0]->getId(), $game->getHandBrainHandMemberId());
        $this->assertSame($membersB[1]->getId(), $game->getHandBrainBrainMemberId());
        $this->assertSame('brain', $game->getHandBrainCurrentRole());
        $this->assertNull($game->getHandBrainPieceHint());

        $game->setHandBrainPieceHint('pawn');
        $game->setHandBrainCurrentRole('hand');
        $service->play(new MakeMoveInput($gameId, 'e7e5', (string) $membersB[0]->getUser()->getId()), $membersB[0]->getUser());
        $this->assertSame($membersA[1]->getId(), $game->getHandBrainHandMemberId());
        $this->assertSame($membersA[2]->getId(), $game->getHandBrainBrainMemberId());
        $this->assertSame('brain', $game->getHandBrainCurrentRole());
        $this->assertNull($game->getHandBrainPieceHint());

        $game->setHandBrainPieceHint('knight');
        $game->setHandBrainCurrentRole('hand');
        $service->play(new MakeMoveInput($gameId, 'g1f3', (string) $membersA[1]->getUser()->getId()), $membersA[1]->getUser());
        $this->assertSame($membersB[1]->getId(), $game->getHandBrainHandMemberId());
        $this->assertSame($membersB[2]->getId(), $game->getHandBrainBrainMemberId());
        $this->assertSame('brain', $game->getHandBrainCurrentRole());
        $this->assertNull($game->getHandBrainPieceHint());
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
