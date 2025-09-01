<?php

namespace App\Tests\Entity;

use App\Entity\Game;
use App\Entity\Team;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class GameTest extends TestCase
{
    private Game $game;

    protected function setUp(): void
    {
        $this->game = new Game();
    }

    public function testGameInitialization(): void
    {
        $this->assertNotEmpty($this->game->getId());
        $this->assertEquals(Game::STATUS_LOBBY, $this->game->getStatus());
        $this->assertEquals('startpos', $this->game->getFen());
        $this->assertEquals(0, $this->game->getPly());
        $this->assertEquals(Game::TEAM_A, $this->game->getTurnTeam());
        $this->assertEquals(60, $this->game->getTurnDurationSec());
        $this->assertEquals('private', $this->game->getVisibility());
        $this->assertFalse($this->game->isFastModeEnabled());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->game->getCreatedAt());
        $this->assertEquals(0, $this->game->getConsecutiveTimeouts());
    }

    public function testSettersAndGetters(): void
    {
        $user = $this->createMock(User::class);
        $now = new \DateTimeImmutable();

        $this->game->setStatus(Game::STATUS_LIVE)
                   ->setCreatedBy($user)
                   ->setUpdatedAt($now)
                   ->setTurnDurationSec(120)
                   ->setVisibility('public')
                   ->setFen('rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq e3 0 1')
                   ->setPly(1)
                   ->setTurnTeam(Game::TEAM_B)
                   ->setResult('1-0');

        $this->assertEquals(Game::STATUS_LIVE, $this->game->getStatus());
        $this->assertEquals($user, $this->game->getCreatedBy());
        $this->assertEquals($now, $this->game->getUpdatedAt());
        $this->assertEquals(120, $this->game->getTurnDurationSec());
        $this->assertEquals('public', $this->game->getVisibility());
        $this->assertEquals('rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq e3 0 1', $this->game->getFen());
        $this->assertEquals(1, $this->game->getPly());
        $this->assertEquals(Game::TEAM_B, $this->game->getTurnTeam());
        $this->assertEquals('1-0', $this->game->getResult());
    }

    public function testTurnDeadline(): void
    {
        $deadline = new \DateTimeImmutable('+10 minutes');
        $this->game->setTurnDeadline($deadline);

        $this->assertEquals($deadline, $this->game->getTurnDeadline());
    }

    public function testFastMode(): void
    {
        $fastDeadline = new \DateTimeImmutable('+1 minute');

        $this->assertFalse($this->game->isFastModeEnabled());

        $this->game->setFastModeEnabled(true)
                   ->setFastModeDeadline($fastDeadline);

        $this->assertTrue($this->game->isFastModeEnabled());
        $this->assertEquals($fastDeadline, $this->game->getFastModeDeadline());
    }

    public function testEffectiveDeadline(): void
    {
        $turnDeadline = new \DateTimeImmutable('+10 minutes');
        $fastDeadline = new \DateTimeImmutable('+1 minute');

        // Sans mode rapide, retourne la deadline normale
        $this->game->setTurnDeadline($turnDeadline);
        $this->assertEquals($turnDeadline, $this->game->getEffectiveDeadline());

        // Avec mode rapide, retourne la deadline rapide
        $this->game->setFastModeEnabled(true)
                   ->setFastModeDeadline($fastDeadline);
        $this->assertEquals($fastDeadline, $this->game->getEffectiveDeadline());

        // Mode rapide activé mais pas de deadline rapide
        $this->game->setFastModeDeadline(null);
        $this->assertEquals($turnDeadline, $this->game->getEffectiveDeadline());
    }

    public function testConsecutiveTimeouts(): void
    {
        $this->assertEquals(0, $this->game->getConsecutiveTimeouts());

        $this->game->incrementConsecutiveTimeouts();
        $this->assertEquals(1, $this->game->getConsecutiveTimeouts());

        $this->game->setConsecutiveTimeouts(3);
        $this->assertEquals(3, $this->game->getConsecutiveTimeouts());

        $this->game->resetConsecutiveTimeouts();
        $this->assertEquals(0, $this->game->getConsecutiveTimeouts());
        $this->assertNull($this->game->getLastTimeoutTeam());
    }

    public function testLastTimeoutTeam(): void
    {
        $this->assertNull($this->game->getLastTimeoutTeam());

        $this->game->setLastTimeoutTeam(Game::TEAM_A);
        $this->assertEquals(Game::TEAM_A, $this->game->getLastTimeoutTeam());

        $this->game->setLastTimeoutTeam(Game::TEAM_B);
        $this->assertEquals(Game::TEAM_B, $this->game->getLastTimeoutTeam());
    }

    public function testCanClaimVictory(): void
    {
        // Initialement, pas de revendication possible
        $this->assertFalse($this->game->canClaimVictory());

        // Avec 2 timeouts, pas encore possible
        $this->game->setConsecutiveTimeouts(2)
                   ->setLastTimeoutTeam(Game::TEAM_A);
        $this->assertFalse($this->game->canClaimVictory());

        // Avec 3 timeouts, possible
        $this->game->setConsecutiveTimeouts(3);
        $this->assertTrue($this->game->canClaimVictory());

        // Sans équipe de timeout, pas possible même avec 3+
        $this->game->setLastTimeoutTeam(null);
        $this->assertFalse($this->game->canClaimVictory());
    }

    public function testGetClaimVictoryTeam(): void
    {
        // Pas de revendication possible initialement
        $this->assertNull($this->game->getClaimVictoryTeam());

        // Si équipe A a fait des timeouts, équipe B peut revendiquer
        $this->game->setConsecutiveTimeouts(3)
                   ->setLastTimeoutTeam(Game::TEAM_A);
        $this->assertEquals(Game::TEAM_B, $this->game->getClaimVictoryTeam());

        // Si équipe B a fait des timeouts, équipe A peut revendiquer
        $this->game->setLastTimeoutTeam(Game::TEAM_B);
        $this->assertEquals(Game::TEAM_A, $this->game->getClaimVictoryTeam());
    }

    public function testTeamManagement(): void
    {
        $teamA = new Team($this->game, Game::TEAM_A);
        $teamB = new Team($this->game, Game::TEAM_B);

        // Ajouter les équipes
        $this->game->addTeam($teamA);
        $this->game->addTeam($teamB);

        $this->assertCount(2, $this->game->getTeams());
        $this->assertTrue($this->game->getTeams()->contains($teamA));
        $this->assertTrue($this->game->getTeams()->contains($teamB));

        // Récupérer une équipe par nom
        $this->assertEquals($teamA, $this->game->getTeamByName(Game::TEAM_A));
        $this->assertEquals($teamB, $this->game->getTeamByName(Game::TEAM_B));
        $this->assertNull($this->game->getTeamByName('INEXISTANT'));

        // Supprimer une équipe
        $this->game->removeTeam($teamA);
        $this->assertCount(1, $this->game->getTeams());
        $this->assertFalse($this->game->getTeams()->contains($teamA));
    }

    public function testAddSameTeamTwice(): void
    {
        $team = new Team($this->game, Game::TEAM_A);

        $this->game->addTeam($team);
        $this->game->addTeam($team); // Ajouter la même équipe deux fois

        $this->assertCount(1, $this->game->getTeams()); // Ne devrait être ajoutée qu'une fois
    }
}
