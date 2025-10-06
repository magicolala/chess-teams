<?php

namespace App\Application\Service\Game\Traits;

use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Entity\Game;
use App\Entity\Team;
use App\Entity\TeamMember;

trait HandBrainTurnHelper
{
    abstract protected function getTeamMemberRepository(): TeamMemberRepositoryInterface;

    protected function refreshHandBrainStateForTeam(Game $game, Team $team): void
    {
        $order = $this->getTeamMemberRepository()->findActiveOrderedByTeam($team);

        if (0 === count($order)) {
            $game->resetHandBrainState();

            return;
        }

        $assignment = $this->resolveHandBrainAssignment($team, $order);

        $game
            ->setHandBrainCurrentRole('brain')
            ->setHandBrainPieceHint(null)
            ->setHandBrainMembers($assignment['brain'], $assignment['hand']);
    }

    /**
     * @param TeamMember[] $order
     *
     * @return array{brain: string, hand: string}
     */
    private function resolveHandBrainAssignment(Team $team, array $order): array
    {
        $count = count($order);
        $handIndex = min(max($team->getCurrentIndex(), 0), $count - 1);
        $hand = $order[$handIndex];
        $brainIndex = $count > 1 ? (($handIndex + 1) % $count) : $handIndex;
        $brain = $order[$brainIndex];

        return [
            'brain' => $brain->getId(),
            'hand' => $hand->getId(),
        ];
    }
}
