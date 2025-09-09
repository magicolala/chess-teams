<?php

namespace App\Tests\Application\UseCase;

use App\Application\DTO\MakeMoveInput;
use App\Application\UseCase\MakeMoveHandler;
use App\Application\Service\GameEndEvaluator;
use App\Application\Port\ChessEngineInterface;
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

class MakeMoveHandlerTest extends TestCase
{
    private function createUser(string $id): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        return $user;
    }

    public function testInvalidUciIsRejectedEarly(): void
    {
        $games = $this->createMock(GameRepositoryInterface::class);
        $teams = $this->createMock(TeamRepositoryInterface::class);
        $members = $this->createMock(TeamMemberRepositoryInterface::class);
        $moves = $this->createMock(MoveRepositoryInterface::class);
        $engine = $this->createMock(ChessEngineInterface::class);
        $lockFactory = $this->createMock(LockFactory::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $evaluator = new GameEndEvaluator(); // final class: use real instance

        // Game mock minimal
        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn('g1');
        $game->method('getStatus')->willReturn('live');
        $game->method('getTurnTeam')->willReturn(Team::NAME_A);
        $game->method('getEffectiveDeadline')->willReturn(null);
        $games->method('get')->with('g1')->willReturn($game);

        // Team A/B mocks
        $teamA = $this->createMock(Team::class);
        $teamB = $this->createMock(Team::class);
        $teamA->method('getName')->willReturn(Team::NAME_A);
        $teamB->method('getName')->willReturn(Team::NAME_B);
        $teamA->method('getCurrentIndex')->willReturn(0);
        $teams->method('findOneByGameAndName')->willReturnMap([
            [$game, Team::NAME_A, $teamA],
            [$game, Team::NAME_B, $teamB],
        ]);

        // Members order
        $tm = $this->createMock(TeamMember::class);
        $byUser = $this->createUser('u1');
        $tmUser = $this->createUser('u1');
        $tm->method('getUser')->willReturn($tmUser);
        $members->method('findActiveOrderedByTeam')->with($teamA)->willReturn([$tm]);

        // Lock
        $lock = $this->createMock(LockInterface::class);
        $lock->method('acquire')->willReturn(true);
        $lockFactory->method('createLock')->willReturn($lock);

        $handler = new MakeMoveHandler($games, $teams, $members, $moves, $engine, $lockFactory, $em, $evaluator);

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('invalid_uci');

        ($handler)(new MakeMoveInput('g1', 'baduci', 'u1'), $byUser);
    }

    public function testSanFallbackToUciWhenEngineReturnsEmptySan(): void
    {
        $games = $this->createMock(GameRepositoryInterface::class);
        $teams = $this->createMock(TeamRepositoryInterface::class);
        $members = $this->createMock(TeamMemberRepositoryInterface::class);
        $moves = $this->createMock(MoveRepositoryInterface::class);
        $engine = $this->createMock(ChessEngineInterface::class);
        $lockFactory = $this->createMock(LockFactory::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $evaluator = new GameEndEvaluator(); // final class: use real instance

        // Game mock with state changes allowed
        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn('g1');
        $game->method('getStatus')->willReturn('live');
        $game->method('getTurnTeam')->willReturn(Team::NAME_A);
        $game->method('getEffectiveDeadline')->willReturn(null);
        $game->method('getFen')->willReturn('fen1');
        $game->method('getPly')->willReturn(0);

        // Allow setters to be called without behavior
        $game->method('setFen')->willReturnSelf();
        $game->method('setPly')->willReturnSelf();
        $game->method('resetConsecutiveTimeouts')->willReturnSelf();
        $game->method('setFastModeEnabled')->willReturnSelf();
        $game->method('setFastModeDeadline')->willReturnSelf();
        $game->method('setTurnDeadline')->willReturnSelf();
        $game->method('setUpdatedAt')->willReturnSelf();
        $game->method('setTurnTeam')->willReturnSelf();

        $games->method('get')->with('g1')->willReturn($game);

        // Teams & rotation
        $teamA = $this->createMock(Team::class);
        $teamB = $this->createMock(Team::class);
        $teamA->method('getName')->willReturn(Team::NAME_A);
        $teamB->method('getName')->willReturn(Team::NAME_B);
        $teamA->method('getCurrentIndex')->willReturn(0);
        $teamA->method('setCurrentIndex')->willReturnSelf();
        $teams->method('findOneByGameAndName')->willReturnMap([
            [$game, Team::NAME_A, $teamA],
            [$game, Team::NAME_B, $teamB],
        ]);

        $tm = $this->createMock(TeamMember::class);
        $byUser = $this->createUser('u1');
        $tmUser = $this->createUser('u1');
        $tm->method('getUser')->willReturn($tmUser);
        $members->method('findActiveOrderedByTeam')->with($teamA)->willReturn([$tm]);

        $lock = $this->createMock(LockInterface::class);
        $lock->method('acquire')->willReturn(true);
        $lockFactory->method('createLock')->willReturn($lock);

        // Engine returns empty SAN to trigger fallback
        $engine->method('applyUci')->willReturn([
            'fenAfter' => 'fen2',
            'san' => '',
        ]);

        // Expect repository to receive a Move with SAN == UCI
        $moves->expects($this->once())
            ->method('add')
            ->with($this->callback(function (Move $m) {
                return $m->getSan() === 'e2e4' && $m->getUci() === 'e2e4';
            }));

        $handler = new MakeMoveHandler($games, $teams, $members, $moves, $engine, $lockFactory, $em, $evaluator);
        $out = ($handler)(new MakeMoveInput('g1', 'e2e4', 'u1'), $byUser);

        $this->assertSame('g1', $out->gameId);
        // Le FEN dans l'output est lu depuis $game->getFen(), que notre mock ne met pas à jour
        // L'objectif de ce test est le fallback SAN => UCI, pas le FEN retourné
        $this->assertSame(1, $out->ply);
    }
}
