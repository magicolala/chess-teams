<?php

namespace App\Tests\Unit\Application\Service;

use App\Application\Service\PgnExporter;
use App\Domain\Repository\MoveRepositoryInterface;
use App\Entity\Game;
use App\Entity\Move;
use App\Entity\Team;
use PHPUnit\Framework\TestCase;

final class PgnExporterTest extends TestCase
{
    public function testExportBuildsHeadersBodyAndResult(): void
    {
        $movesRepo = $this->createMock(MoveRepositoryInterface::class);
        $exporter = new PgnExporter($movesRepo);

        $g = (new Game())
            ->setFen('startpos')
            ->setPly(2)
            ->setTurnTeam(Team::NAME_A)
            ->setResult('A#');

        $m1 = (new Move($g, 1))
            ->setTeam(new Team($g, Team::NAME_A))
            ->setUci('e2e4')
            ->setSan('e4')
            ->setFenAfter('...')
            ->setType(Move::TYPE_NORMAL)
        ;
        $m2 = (new Move($g, 2))
            ->setTeam(new Team($g, Team::NAME_B))
            ->setUci('e7e5')
            ->setSan('e5')
            ->setFenAfter('...')
            ->setType(Move::TYPE_NORMAL)
        ;

        $movesRepo->method('listByGameOrdered')->with($g)->willReturn([$m1, $m2]);

        $pgn = $exporter->export($g);

        self::assertStringContainsString('[Event "Chess Teams Game"]', $pgn);
        self::assertStringContainsString('[Site "chess-teams"]', $pgn);
        self::assertStringContainsString('[Result "1-0"]', $pgn, 'A# should map to 1-0');
        self::assertStringContainsString('1. e4 e5 1-0', $pgn);
    }

    public function testExportSkipsNonNormalMovesAndAddsComments(): void
    {
        $movesRepo = $this->createMock(MoveRepositoryInterface::class);
        $exporter = new PgnExporter($movesRepo);

        $g = new Game();
        $g->setResult(null);

        $timeout = (new Move($g, 1))
            ->setTeam(new Team($g, Team::NAME_A))
            ->setFenAfter('...')
            ->setType(Move::TYPE_TIMEOUT);

        $movesRepo->method('listByGameOrdered')->with($g)->willReturn([$timeout]);

        $pgn = $exporter->export($g);
        self::assertStringContainsString('{'.Move::TYPE_TIMEOUT.'}', $pgn);
        self::assertStringContainsString(' *', $pgn, 'Ongoing games print *');
    }
}
