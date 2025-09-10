<?php

namespace App\Application\Service;

use App\Domain\Repository\MoveRepositoryInterface;
use App\Entity\Game;
use App\Entity\Move;

final class PgnExporter
{
    public function __construct(private MoveRepositoryInterface $moves)
    {
    }

    public function export(Game $game): string
    {
        $headers = [];
        $headers[] = sprintf('[Event "%s"]', 'Chess Teams Game');
        $headers[] = sprintf('[Site "%s"]', 'chess-teams');
        $headers[] = sprintf('[Date "%s"]', $game->getCreatedAt()->format('Y.m.d'));
        $headers[] = sprintf('[Round "%s"]', '1');
        $headers[] = sprintf('[White "%s"]', 'Team A');
        $headers[] = sprintf('[Black "%s"]', 'Team B');

        $resultTag = $this->mapResultToTag($game->getResult());
        $headers[] = sprintf('[Result "%s"]', $resultTag);

        $body = $this->renderMoves($game);

        return implode("\n", $headers)."\n\n".$body.' '.$resultTag."\n";
    }

    private function renderMoves(Game $game): string
    {
        $moves = $this->moves->listByGameOrdered($game);
        $parts = [];
        foreach ($moves as $mv) {
            if (!$mv instanceof Move) {
                continue;
            }
            // Ignore non-normal moves in PGN mainline, optionally annotate
            if (Move::TYPE_NORMAL !== $mv->getType()) {
                // Add a comment to indicate timeout/pass without polluting mainline
                $parts[] = sprintf('{%s}', $mv->getType());
                continue;
            }

            $ply = $mv->getPly();
            $san = trim((string) $mv->getSan());
            if ('' === $san) {
                $san = trim((string) $mv->getUci() ?? '');
                if ('' === $san) {
                    continue;
                }
            }

            // Add move number for White moves (odd ply)
            if (1 === $ply % 2) {
                $moveNumber = intdiv($ply + 1, 2);
                $parts[] = $moveNumber.'.';
            }
            $parts[] = $san;
        }

        return implode(' ', $parts);
    }

    private function mapResultToTag(?string $result): string
    {
        // Map internal result formats to PGN result strings
        // Internal examples: 'A#', 'B#', '1/2-1/2', 'resignA', 'resignB', 'timeoutA', 'timeoutB'
        return match ($result) {
            '1/2-1/2' => '1/2-1/2',
            'A#', 'resignB', 'timeoutB' => '1-0',
            'B#', 'resignA', 'timeoutA' => '0-1',
            default => '*', // ongoing or unknown
        };
    }
}
