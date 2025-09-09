<?php

namespace App\Infrastructure\Chess;

use App\Application\Port\ChessEngineInterface;
use Chess\Variant\Classical\FenToBoardFactory;

final class ChesslablabEngine implements ChessEngineInterface
{
    public function applyUci(string $fen, string $uci): array
    {
        // Validate UCI (e2e4, e7e8q, etc.)
        if (!preg_match('/^[a-h][1-8][a-h][1-8]([qrbn])?$/i', $uci)) {
            throw new \InvalidArgumentException('invalid_uci');
        }

        // Normalize 'startpos' to a valid initial FEN
        $fenToLoad = ('startpos' === $fen)
            ? 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1'
            : $fen;

        try {
            $board = FenToBoardFactory::create($fenToLoad);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('invalid_fen');
        }
        try {
            // chesslablab expects long algebraic without dashes (e2e4) and a turn argument
            $before = is_array($board->history) ? count($board->history) : 0;
            $board->playLan($board->turn, $uci);
            $after = is_array($board->history) ? count($board->history) : 0;
            if ($after <= $before) {
                throw new \InvalidArgumentException('illegal_move');
            }
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('illegal_move');
        }

        $lastMove = end($board->history);

        // Ensure SAN includes '=' for promotions when input UCI has a promotion letter
        $san = $lastMove['pgn'] ?? strtoupper($uci);
        if (5 === strlen($uci)) {
            // Promotion detected in input
            if (false === strpos($san, '=')) {
                // Build minimal SAN with '=' if missing
                $to = substr($uci, 2, 2);
                $promo = strtoupper($uci[4]);
                $san = strtoupper($to).'='.$promo;
            }
        }

        return [
            'fenAfter' => $board->toFen(),
            'san' => $san,
        ];
    }
}
