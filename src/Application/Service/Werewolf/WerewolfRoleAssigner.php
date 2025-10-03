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

            $wolfRoleA = array_find(
                $roles,
                static fn (GameRole $role): bool => $wolfA
                    && $role->getUser()->getId() === $wolfA->getId()
                    && Game::TEAM_A === $role->getTeamName()
            );
            if ($wolfRoleA instanceof GameRole) {
                $wolfRoleA->setRole('werewolf');
            }

            $wolfRoleB = array_find(
                $roles,
                static fn (GameRole $role): bool => $wolfB
                    && $role->getUser()->getId() === $wolfB->getId()
                    && Game::TEAM_B === $role->getTeamName()
            );
            if ($wolfRoleB instanceof GameRole) {
                $wolfRoleB->setRole('werewolf');
            }
        } else {
            // single werewolf across all players
            $idx = $this->pickIndex(count($all));
            $wolf = $all[$idx] ?? null;
            if ($wolf) {
                $wolfRole = array_find(
                    $roles,
                    static fn (GameRole $role): bool => $role->getUser()->getId() === $wolf->getId()
                );
                if ($wolfRole instanceof GameRole) {
                    $wolfRole->setRole('werewolf');
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
