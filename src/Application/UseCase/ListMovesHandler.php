<?php

namespace App\Application\UseCase;

use App\Application\DTO\ListMovesInput;
use App\Application\DTO\ListMovesOutput;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\MoveRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ListMovesHandler
{
    public function __construct(
        private GameRepositoryInterface $games,
        private MoveRepositoryInterface $moves,
    ) {
    }

    public function __invoke(ListMovesInput $in): ListMovesOutput
    {
        $g = $this->games->get($in->gameId);
        if (!$g) {
            throw new NotFoundHttpException('game_not_found');
        }

        $rows = [];
        foreach ($this->moves->listByGameOrdered($g) as $m) {
            $rows[] = [
                'ply'       => $m->getPly(),
                'team'      => $m->getTeam()?->getName(),
                'byUserId'  => $m->getByUser()?->getId(),
                'uci'       => $m->getUci(),
                'san'       => $m->getSan(),
                'type'      => method_exists($m, 'getType') ? $m->getType() : null,
                'fenAfter'  => $m->getFenAfter(),
                'createdAt' => $m->getCreatedAt()?->format(DATE_ATOM),
            ];
        }

        return new ListMovesOutput($g->getId(), $rows);
    }
}
