<?php
namespace App\Application\UseCase;

use App\Application\DTO\{StartGameInput, StartGameOutput};
use App\Domain\Repository\{
    GameRepositoryInterface,
    TeamRepositoryInterface,
    TeamMemberRepositoryInterface
};
use App\Entity\{Game, Team, User};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\{NotFoundHttpException, ConflictHttpException, AccessDeniedHttpException};

final class StartGameHandler
{
    public function __construct(
        private GameRepositoryInterface $games,
        private TeamRepositoryInterface $teams,
        private TeamMemberRepositoryInterface $members,
        private EntityManagerInterface $em
    ) {}

    public function __invoke(StartGameInput $in, User $requestedBy): StartGameOutput
    {
        $game = $this->games->get($in->gameId);
        if (!$game) {
            throw new NotFoundHttpException('game_not_found');
        }

        // autorisation simple : seul le créateur peut démarrer (tu pourras élargir après)
        if ($game->getCreatedBy()?->getId() !== $requestedBy->getId()) {
            throw new AccessDeniedHttpException('only_creator_can_start');
        }

        if ($game->getStatus() !== Game::STATUS_LOBBY) {
            throw new ConflictHttpException('already_started_or_finished');
        }

        $teamA = $this->teams->findOneByGameAndName($game, Team::NAME_A);
        $teamB = $this->teams->findOneByGameAndName($game, Team::NAME_B);
        if (!$teamA || !$teamB) {
            throw new NotFoundHttpException('teams_not_found');
        }

        $countA = $this->members->countActiveByTeam($teamA);
        $countB = $this->members->countActiveByTeam($teamB);
        if ($countA === 0 || $countB === 0) {
            throw new ConflictHttpException('each_team_must_have_at_least_one_member');
        }

        $now = new \DateTimeImmutable();
        $deadline = $now->modify('+'.$game->getTurnDurationSec().' seconds');

        $game
            ->setStatus(Game::STATUS_LIVE)
            ->setTurnTeam(Game::TEAM_A)
            ->setTurnDeadline($deadline)
            ->setUpdatedAt($now);

        $this->em->flush();

        return new StartGameOutput(
            gameId: $game->getId(),
            status: $game->getStatus(),
            turnTeam: $game->getTurnTeam(),
            turnDeadlineTs: $deadline->getTimestamp() * 1000
        );
    }
}
