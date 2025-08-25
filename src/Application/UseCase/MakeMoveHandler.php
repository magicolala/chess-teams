<?php

namespace App\Application\UseCase;

use App\Application\DTO\MakeMoveInput;
use App\Application\DTO\MakeMoveOutput;
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
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Lock\LockFactory;

final class MakeMoveHandler
{
    public function __construct(
        private GameRepositoryInterface $games,
        private TeamRepositoryInterface $teams,
        private TeamMemberRepositoryInterface $members,
        private MoveRepositoryInterface $moves,
        private ChessEngineInterface $engine,
        private LockFactory $lockFactory,
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(MakeMoveInput $in, User $byUser): MakeMoveOutput
    {
        $game = $this->games->get($in->gameId);
        if (!$game) {
            throw new NotFoundHttpException('game_not_found');
        }
        if (Game::STATUS_LIVE !== $game->getStatus()) {
            throw new ConflictHttpException('game_not_live');
        }

        // Lock sur la partie (évite les moves concurrents)
        $lock = $this->lockFactory->createLock('game:'.$game->getId(), 5.0);
        if (!$lock->acquire()) {
            throw new ConflictHttpException('locked');
        }

        try {
            // équipes
            $teamA = $this->teams->findOneByGameAndName($game, Team::NAME_A);
            $teamB = $this->teams->findOneByGameAndName($game, Team::NAME_B);
            if (!$teamA || !$teamB) {
                throw new NotFoundHttpException('teams_not_found');
            }

            $teamToPlay = Team::NAME_A === $game->getTurnTeam() ? $teamA : $teamB;
            $othersTeam = Team::NAME_A === $game->getTurnTeam() ? $teamB : $teamA;

            // ordre des joueurs de l'équipe au trait
            $order = $this->members->findActiveOrderedByTeam($teamToPlay);
            if (!$order) {
                throw new ConflictHttpException('no_players_in_team_to_play');
            }

            $idx = $teamToPlay->getCurrentIndex();
            $idx = max(0, min($idx, count($order) - 1)); // garde bornes
            /** @var TeamMember $mustPlay */
            $mustPlay = $order[$idx];

            if ($mustPlay->getUser()->getId() !== $byUser->getId()) {
                throw new AccessDeniedHttpException('not_your_turn_in_team');
            }

            // deadline
            $now = new \DateTimeImmutable();
            if ($game->getTurnDeadline() && $now > $game->getTurnDeadline()) {
                throw new ConflictHttpException('turn_expired');
            }

            // appliquer le coup via le moteur
            try {
                $result = $this->engine->applyUci($game->getFen(), $in->uci);
            } catch (\InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException('illegal_move');
            }

            $fenAfter = $result['fenAfter'];
            $san      = $result['san'];

            // persist Move
            $ply = $game->getPly() + 1;
            $mv  = new Move($game, $ply);
            $mv->setTeam($teamToPlay)->setByUser($byUser)->setUci($in->uci)->setSan($san)->setFenAfter($fenAfter);
            $this->moves->add($mv);

            // avancer état partie
            $game->setFen($fenAfter);
            $game->setPly($ply);
            // avançe l'index de l'équipe qui vient de jouer
            $n = count($order);
            $teamToPlay->setCurrentIndex(($idx + 1) % $n);

            // changer de camp / deadline
            $game->setTurnTeam($othersTeam->getName());
            $deadline = $now->modify('+'.$game->getTurnDurationSec().' seconds');
            $game->setTurnDeadline($deadline);
            $game->setUpdatedAt($now);

            $this->em->flush();

            return new MakeMoveOutput(
                $game->getId(),
                $ply,
                $game->getTurnTeam(),
                $deadline->getTimestamp() * 1000,
                $game->getFen()
            );
        } finally {
            $lock->release();
        }
    }
}
