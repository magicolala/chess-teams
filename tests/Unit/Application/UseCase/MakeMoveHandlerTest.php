<?php

namespace App\Tests\Unit\Application\UseCase;

use App\Application\DTO\MakeMoveInput;
use App\Application\Port\ChessEngineInterface;
use App\Application\Service\GameEndEvaluator;
use App\Application\Service\Werewolf\WerewolfVoteService;
use App\Application\UseCase\MakeMoveHandler;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\MoveRepositoryInterface;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class MakeMoveHandlerTest extends TestCase
{
    public function testMakeMoveHappyPath(): void
    {
        $games = $this->createMock(GameRepositoryInterface::class);
        $teams = $this->createMock(TeamRepositoryInterface::class);
        $members = $this->createMock(TeamMemberRepositoryInterface::class);
        $moves = $this->createMock(MoveRepositoryInterface::class);
        $engine = $this->createMock(ChessEngineInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);

        // Lock always acquired
        $lock = $this->createMock(LockInterface::class);
        $lock->method('acquire')->willReturn(true);
        $lock->expects(self::once())->method('release');
        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->method('createLock')->willReturn($lock);

        // Pas de mock (classe finale) : on prend l’implé réelle.
        // Sur notre scénario (e2e4 depuis startpos), l’évaluateur ne finira pas la partie.
        $end = new GameEndEvaluator();
        $werewolf = $this->createMock(WerewolfVoteService::class);

        // ⚠️ Vérifie l'ordre des arguments selon TA classe :
        // MakeMoveHandler::__construct(
        //   GameRepositoryInterface, TeamRepositoryInterface, TeamMemberRepositoryInterface,
        //   MoveRepositoryInterface, ChessEngineInterface, LockFactory, EntityManagerInterface, GameEndEvaluator, WerewolfVoteService
        // )
        $handler = new MakeMoveHandler($games, $teams, $members, $moves, $engine, $lockFactory, $em, $end, $werewolf);

        $uA = new User();
        $uA->setEmail('a@test.io');
        $uA->setPassword('x');
        $g = (new Game())
            ->setStatus(Game::STATUS_LIVE)
            ->setTurnTeam(Team::NAME_A)
            ->setFen('startpos')
            ->setPly(0)
            ->setTurnDurationSec(60)
        ;

        $games->method('get')->willReturn($g);
        $tA = new Team($g, Team::NAME_A);
        $tB = new Team($g, Team::NAME_B);

        $teams->method('findOneByGameAndName')->willReturnMap([
            [$g, Team::NAME_A, $tA],
            [$g, Team::NAME_B, $tB],
        ]);

        $members->method('findActiveOrderedByTeam')->willReturnMap([
            [$tA, [new TeamMember($tA, $uA, 0)]],
        ]);

        $engine->method('applyUci')->with('startpos', 'e2e4')->willReturn([
            // FEN valide après 1.e4 (au trait: noir)
            'fenAfter' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq - 0 1',
            'san' => 'e4',
        ]);

        $moves->expects(self::once())->method('add');
        $em->expects(self::once())->method('flush');

        $out = $handler(new MakeMoveInput($g->getId(), 'e2e4', $uA->getId() ?? ''), $uA);

        self::assertSame(1, $out->ply);
        self::assertSame('B', $out->turnTeam);
        self::assertSame('rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq - 0 1', $out->fen);
    }
}
