<?php

namespace App\Application\Service\Werewolf;

use App\Entity\Game;
use App\Entity\GameRole;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class WerewolfRoleAssigner
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * Assign roles for a game according to werewolf options.
     * - If $twoWolvesEnabled and eligible, assigns 1 werewolf per team.
     * - Else assigns a single werewolf randomly across all players.
     *
     * Returns list of GameRole created.
     */
    public function assignForGame(Game $game, array $teamAUsers, array $teamBUsers): array
    {
        $roles = [];
        $all = array_merge($teamAUsers, $teamBUsers);
        // must have at least 4 players to enable werewolf mode assignment
        if (count($all) < 4) {
            return [];
        }

        // Default: everyone villager
        foreach ($teamAUsers as $u) {
            if ($u instanceof User) {
                $roles[] = new GameRole($game, $u, Game::TEAM_A, 'villager');
            }
        }
        foreach ($teamBUsers as $u) {
            if ($u instanceof User) {
                $roles[] = new GameRole($game, $u, Game::TEAM_B, 'villager');
            }
        }

        $rng = random_int(0, PHP_INT_MAX); // seed-like value for branching

        // Choose werewolves
        if ($game->isTwoWolvesEnabled() && count($teamAUsers) >= 1 && count($teamBUsers) >= 1) {
            // one werewolf per team
            $idxA = $this->pickIndex(count($teamAUsers));
            $idxB = $this->pickIndex(count($teamBUsers));

            $wolfA = $teamAUsers[$idxA] ?? null;
            $wolfB = $teamBUsers[$idxB] ?? null;

            foreach ($roles as $r) {
                if ($wolfA && $r->getUser()->getId() === $wolfA->getId() && Game::TEAM_A === $r->getTeamName()) {
                    $r->setRole('werewolf');
                }
                if ($wolfB && $r->getUser()->getId() === $wolfB->getId() && Game::TEAM_B === $r->getTeamName()) {
                    $r->setRole('werewolf');
                }
            }
        } else {
            // single werewolf across all players
            $idx = $this->pickIndex(count($all));
            $wolf = $all[$idx] ?? null;
            if ($wolf) {
                foreach ($roles as $r) {
                    if ($r->getUser()->getId() === $wolf->getId()) {
                        $r->setRole('werewolf');
                        break;
                    }
                }
            }
        }

        foreach ($roles as $r) {
            $this->em->persist($r);
        }
        $this->em->flush();

        return $roles;
    }

    private function pickIndex(int $n): int
    {
        if ($n <= 1) {
            return 0;
        }

        return random_int(0, $n - 1);
    }
}
