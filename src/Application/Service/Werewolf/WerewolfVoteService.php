<?php

namespace App\Application\Service\Werewolf;

use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Entity\Game;
use App\Entity\GameRole;
use App\Entity\GameWerewolfVote;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class WerewolfVoteService
{
    public function __construct(
        private EntityManagerInterface $em,
        private TeamMemberRepositoryInterface $members,
        private WerewolfScoringService $scoring,
    ) {
    }

    public function openVote(Game $game): void
    {
        $game->setVoteOpen(true);
        $game->setVoteStartedAt(new \DateTimeImmutable());
        $this->em->flush();
    }

    public function castVote(Game $game, User $voter, User $suspect): GameWerewolfVote
    {
        // Enforce participant-only voting
        $participant = $this->members->findOneByGameAndUser($game, $voter);
        if (!$participant || !$participant->isActive()) {
            throw new \RuntimeException('not_a_participant');
        }

        // Unique constraint ensures one vote per voter per game
        $vote = new GameWerewolfVote($game, $voter, $suspect);
        $this->em->persist($vote);
        $this->em->flush();

        // Auto-close if all active participants have voted
        $total = $this->members->countActiveByGame($game);
        $repo = $this->em->getRepository(GameWerewolfVote::class);
        $countVotes = $repo->count(['game' => $game]);
        if ($total > 0 && $countVotes >= $total) {
            $this->closeVote($game);
        }

        return $vote;
    }

    /**
     * Return an array of [suspectUserId => count]
     */
    public function getLiveResults(Game $game): array
    {
        // Skeleton: naive count via repository later replaced by DQL
        $repo = $this->em->getRepository(GameWerewolfVote::class);
        $votes = $repo->findBy(['game' => $game]);
        $counts = [];
        foreach ($votes as $v) {
            $id = $v->getSuspect()->getId();
            $counts[$id] = ($counts[$id] ?? 0) + 1;
        }

        return $counts;
    }

    public function closeVote(Game $game): void
    {
        // Compute majority
        $repo = $this->em->getRepository(GameWerewolfVote::class);
        $votes = $repo->findBy(['game' => $game]);
        $totalVotes = \count($votes);

        $tally = [];
        foreach ($votes as $v) {
            $sid = $v->getSuspect()->getId();
            $tally[$sid] = ($tally[$sid] ?? 0) + 1;
        }

        // Determine top suspect and majority (strict > half of expressed votes)
        $majoritySuspectId = null;
        $top = 0;
        foreach ($tally as $sid => $cnt) {
            if ($cnt > $top) {
                $top = $cnt;
                $majoritySuspectId = $sid;
            } elseif ($cnt === $top) {
                $majoritySuspectId = null; // tie
            }
        }
        $hasMajority = ($majoritySuspectId !== null) && ($top > ($totalVotes / 2));

        // Map roles to identify werewolves and their teams
        $roleRepo = $this->em->getRepository(GameRole::class);
        $roles = $roleRepo->findBy(['game' => $game]);
        $werewolves = [];
        $roleByUser = [];
        foreach ($roles as $r) {
            $roleByUser[$r->getUser()->getId()] = ['role' => $r->getRole(), 'team' => $r->getTeamName(), 'user' => $r->getUser()];
            if ('werewolf' === $r->getRole()) {
                $werewolves[$r->getUser()->getId()] = ['team' => $r->getTeamName(), 'user' => $r->getUser()];
            }
        }

        $votersCorrect = [];
        $werewolvesToReward = [];

        // Helper to determine losing team from result
        $losingTeam = $this->inferLosingTeam($game->getResult());
        $isDraw = '1/2-1/2' === $game->getResult();

        if ($hasMajority) {
            // reward voters who picked the suspect if that suspect is a werewolf
            $isWerewolf = isset($roleByUser[$majoritySuspectId]) && 'werewolf' === ($roleByUser[$majoritySuspectId]['role'] ?? null);
            if ($isWerewolf) {
                foreach ($votes as $v) {
                    if ($v->getSuspect()->getId() === $majoritySuspectId) {
                        $votersCorrect[] = $v->getVoter();
                    }
                }
            }
        } else {
            // No majority
            if (0 === $totalVotes) {
                if ($isDraw) {
                    // special case: draw + no votes -> reward all werewolves
                    foreach ($werewolves as $info) {
                        $werewolvesToReward[] = $info['user'];
                    }
                } elseif (in_array($losingTeam, [Game::TEAM_A, Game::TEAM_B], true)) {
                    foreach ($werewolves as $uid => $info) {
                        if ($info['team'] === $losingTeam) {
                            $werewolvesToReward[] = $info['user'];
                        }
                    }
                }
            } else {
                // Votes but no majority: reward werewolf of losing team if any
                if (in_array($losingTeam, [Game::TEAM_A, Game::TEAM_B], true)) {
                    foreach ($werewolves as $uid => $info) {
                        if ($info['team'] === $losingTeam) {
                            $werewolvesToReward[] = $info['user'];
                        }
                    }
                }
            }
        }

        // Apply scoring
        $this->scoring->applyScoring($game, $votersCorrect, $werewolvesToReward);

        $game->setVoteOpen(false);
        $this->em->flush();
    }

    private function inferLosingTeam(?string $result): ?string
    {
        if (null === $result) {
            return null;
        }
        if (preg_match('/^([AB])#$/', $result, $m)) {
            return ($m[1] === Game::TEAM_A) ? Game::TEAM_B : Game::TEAM_A;
        }
        if (str_starts_with($result, 'resignA') || str_starts_with($result, 'timeoutA')) {
            return Game::TEAM_A;
        }
        if (str_starts_with($result, 'resignB') || str_starts_with($result, 'timeoutB')) {
            return Game::TEAM_B;
        }
        if ('1/2-1/2' === $result) {
            return null; // draw
        }

        return null;
    }
}
