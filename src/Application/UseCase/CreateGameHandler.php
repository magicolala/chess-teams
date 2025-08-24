<?php

namespace App\Application\UseCase;

use App\Application\DTO\CreateGameInput;
use App\Application\DTO\CreateGameOutput;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\InviteRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\Invite;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class CreateGameHandler
{
    public function __construct(
        private GameRepositoryInterface $games,
        private TeamRepositoryInterface $teams,
        private InviteRepositoryInterface $invites,
        private EntityManagerInterface $em
    ) {
    }

    public function __invoke(CreateGameInput $in, User $creator): CreateGameOutput
    {
        // 1) Créer Game
        $g = (new Game())
            ->setCreatedBy($creator)
            ->setTurnDurationSec(max(10, $in->turnDurationSec))
            ->setVisibility($in->visibility)
            ->setFen('startpos')
            ->setPly(0)
            ->setTurnTeam(Game::TEAM_A);

        $this->games->add($g);

        // 2) Créer équipes A/B
        $ta = new Team($g, Team::NAME_A);
        $tb = new Team($g, Team::NAME_B);
        $this->teams->add($ta);
        $this->teams->add($tb);

        // 3) Créer code d’invite
        $code = substr(bin2hex(random_bytes(8)), 0, 12);
        $inv = new Invite($g, $code);
        $this->invites->add($inv);

        // 4) Flush 1 fois
        $this->em->flush();

        return new CreateGameOutput($g->getId(), $code, $g->getTurnDurationSec());
    }
}
