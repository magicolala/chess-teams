<?php

namespace App\Application\Service\Werewolf;

use App\Entity\Game;
use App\Entity\GameWerewolfScoreLog;
use App\Entity\UserWerewolfStats;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class WerewolfScoringService
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * Skeleton: apply scoring based on majority result and game outcome.
     * Parameters are placeholders; will be wired with actual vote tally & roles.
     */
    public function applyScoring(Game $game, array $votersCorrect, array $werewolvesToReward): void
    {
        // +1 for each correct voter
        foreach ($votersCorrect as $user) {
            if ($user instanceof User) {
                $this->touchStats($user, 'found');
                $this->em->persist(new GameWerewolfScoreLog($game, $user, GameWerewolfScoreLog::REASON_FOUND));
            }
        }

        // +1 for each werewolf success
        foreach ($werewolvesToReward as $user) {
            if ($user instanceof User) {
                $this->touchStats($user, 'success');
                $this->em->persist(new GameWerewolfScoreLog($game, $user, GameWerewolfScoreLog::REASON_SUCCESS));
            }
        }

        $this->em->flush();
    }

    private function touchStats(User $user, string $type): void
    {
        $repo = $this->em->getRepository(UserWerewolfStats::class);
        $stats = $repo->findOneBy(['user' => $user]);
        if (!$stats) {
            $stats = new UserWerewolfStats($user);
            $this->em->persist($stats);
        }
        if ('found' === $type) {
            $stats->addCorrectIdentification();
        } elseif ('success' === $type) {
            $stats->addWerewolfSuccess();
        }
    }
}
