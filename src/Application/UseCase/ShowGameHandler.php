<?php

namespace App\Application\UseCase;

use App\Application\DTO\ShowGameInput;
use App\Application\DTO\ShowGameOutput;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Team;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShowGameHandler
{
    public function __construct(
        private GameRepositoryInterface $games,
        private TeamRepositoryInterface $teams,
        private TeamMemberRepositoryInterface $members,
    ) {
    }

    public function __invoke(ShowGameInput $in): ShowGameOutput
    {
        $g = $this->games->get($in->gameId);
        if (!$g) {
            throw new NotFoundHttpException('game_not_found');
        }

        $ta = $this->teams->findOneByGameAndName($g, Team::NAME_A);
        $tb = $this->teams->findOneByGameAndName($g, Team::NAME_B);
        if (!$ta || !$tb) {
            throw new NotFoundHttpException('teams_not_found');
        }

        $listA = $this->members->findActiveOrderedByTeam($ta);
        $listB = $this->members->findActiveOrderedByTeam($tb);

        $map = static fn ($arr) => array_map(static function ($m) {
            $u = $m->getUser();

            return [
                'userId' => method_exists($u, 'getId') ? $u->getId() : null,
                'displayName' => method_exists($u, 'getDisplayName') ? $u->getDisplayName() : null,
                'position' => $m->getPosition(),
                'ready' => $m->isReadyToStart(),
            ];
        }, $arr);

        return new ShowGameOutput(
            id: $g->getId(),
            status: $g->getStatus(),
            fen: $g->getFen(),
            ply: $g->getPly(),
            turnTeam: $g->getTurnTeam(),
            turnDeadlineTs: $g->getTurnDeadline()?->getTimestamp() * 1000,
            teamA: ['currentIndex' => $ta->getCurrentIndex(), 'members' => $map($listA)],
            teamB: ['currentIndex' => $tb->getCurrentIndex(), 'members' => $map($listB)],
        );
    }
}
