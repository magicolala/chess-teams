<?php

namespace App\Application\UseCase;

use App\Application\DTO\MakeMoveInput;
use App\Application\DTO\MakeMoveOutput;
use App\Application\Port\ChessEngineInterface;
use App\Application\Service\GameEndEvaluator;
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
        private GameEndEvaluator $endEvaluator,
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
        if (Game::STATUS_FINISHED === $game->getStatus()) {
            throw new ConflictHttpException('game_finished');
        }
        if ($game->isTimeoutDecisionPending()) {
            throw new ConflictHttpException('timeout_decision_pending');
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

            // Vérifier le délai effectif (mode rapide ou mode libre)
            $now = new \DateTimeImmutable();
            $effectiveDeadline = $game->getEffectiveDeadline();
            if ($effectiveDeadline && $now > $effectiveDeadline) {
                throw new ConflictHttpException('turn_expired');
            }

            // Valider UCI basique côté serveur (évite coups vides ou mal formés)
            $uci = \trim((string) $in->uci);
            if ($uci === '' || !\preg_match('/^[a-h][1-8][a-h][1-8][qrbn]?$/i', $uci)) {
                throw new UnprocessableEntityHttpException('invalid_uci');
            }
            // appliquer le coup via le moteur
            try {
                $result = $this->engine->applyUci($game->getFen(), $uci);
            } catch (\InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException('illegal_move');
            }

            $fenAfter = (string) ($result['fenAfter'] ?? '');
            $san = \trim((string) ($result['san'] ?? ''));
            if ($san === '') {
                // Fallback: garantir que SAN ne soit pas null/empty pour les coups normaux
                $san = $uci;
            }

            // persist Move
            $ply = $game->getPly() + 1;
            $mv = new Move($game, $ply);
            $mv
                ->setTeam($teamToPlay)
                ->setByUser($byUser)
                ->setUci($uci)
                ->setSan($san)
                ->setFenAfter($fenAfter)
            ;
            $this->moves->add($mv);

            // avancer état partie
            $game->setFen($fenAfter);
            $game->setPly($ply);
            // avançe l'index de l'équipe qui vient de jouer
            $n = count($order);
            $teamToPlay->setCurrentIndex(($idx + 1) % $n);

            // changer de camp / deadline
            $game->setTurnTeam($othersTeam->getName());

            // Remise à zéro des timeouts consécutifs quand un coup est joué normalement
            $game->resetConsecutiveTimeouts();

            // Réinitialiser le mode rapide pour le nouveau tour (mode libre par défaut = 14 jours max)
            $game->setFastModeEnabled(false);
            $game->setFastModeDeadline(null);
            $deadline = $now->modify('+14 days'); // Mode libre : 14 jours maximum
            $game->setTurnDeadline($deadline);
            $game->setUpdatedAt($now);

            // -> ici, on évalue la fin :
            $end = $this->endEvaluator->evaluateAndApply($game);
            if ($end['isOver']) {
                // si fini, plus de deadline ni tour
                $game->setTurnDeadline(null);
            }

            $this->em->flush();

            return new MakeMoveOutput(
                $game->getId(),
                $ply,
                $game->getTurnTeam(),
                $game->getTurnDeadline()?->getTimestamp() * 1000 ?? 0,
                $game->getFen()
            );
        } finally {
            $lock->release();
        }
    }
}
