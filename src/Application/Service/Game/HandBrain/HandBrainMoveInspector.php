<?php

namespace App\Application\Service\Game\HandBrain;

final class HandBrainMoveInspector
{
    private const INITIAL_FEN = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

    public function resolvePieceType(string $fen, string $uci): ?string
    {
        if (!preg_match('/^[a-h][1-8]/', $uci)) {
            return null;
        }

        $normalizedFen = $this->normalizeFen($fen);
        $normalizedFen = trim($normalizedFen);
        $spacePosition = strpos($normalizedFen, ' ');
        if (false === $spacePosition) {
            $board = $normalizedFen;
        } else {
            $board = substr($normalizedFen, 0, $spacePosition);
        }

        if ('' === $board) {
            return null;
        }

        $ranks = explode('/', $board);
        if (8 !== count($ranks)) {
            return null;
        }

        $fromFile = ord($uci[0]) - ord('a');
        $fromRank = (int) $uci[1];
        if ($fromFile < 0 || $fromFile > 7 || $fromRank < 1 || $fromRank > 8) {
            return null;
        }

        $rowIndex = 8 - $fromRank;
        $row = $ranks[$rowIndex];
        $column = 0;
        foreach (str_split($row) as $symbol) {
            if (ctype_digit($symbol)) {
                $column += (int) $symbol;
                if ($column > 7) {
                    break;
                }

                continue;
            }

            if ($column === $fromFile) {
                return $this->mapPieceLetterToType($symbol);
            }

            ++$column;
        }

        return null;
    }

    private function normalizeFen(string $fen): string
    {
        $trimmed = trim($fen);
        if ('' === $trimmed || 'startpos' === $trimmed) {
            return self::INITIAL_FEN;
        }

        return $trimmed;
    }

    private function mapPieceLetterToType(string $symbol): ?string
    {
        return match (strtolower($symbol)) {
            'p' => 'pawn',
            'n' => 'knight',
            'b' => 'bishop',
            'r' => 'rook',
            'q' => 'queen',
            'k' => 'king',
            default => null,
        };
    }
}
