<?php

namespace App\Infrastructure\Chess;

use App\Application\Port\ChessEngineInterface;
use PChess\Chess\Chess;
use PChess\Chess\Move;
use PChess\Chess\Board;

final class PChessEngine implements ChessEngineInterface
{
    public function applyUci(string $fen, string $uci): array
    {
        // UCI basique: e2e4, e7e8q...
        if (!preg_match('/^[a-h][1-8][a-h][1-8]([qrbn])?$/i', $uci)) {
            throw new \InvalidArgumentException('invalid_uci');
        }

        // 'startpos' => FEN par défaut de la lib
        $fenToLoad = ($fen === 'startpos') ? Board::DEFAULT_POSITION : $fen;

        try {
            // Le constructeur valide la FEN et peut jeter une InvalidArgumentException
            $chess = new Chess($fenToLoad);
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException('invalid_fen');
        }

        // Parse UCI -> array attendu par p-chess/chess
        $from  = substr($uci, 0, 2);
        $to    = substr($uci, 2, 2);
        $promo = strlen($uci) === 5 ? strtolower($uci[4]) : null; // 'q','r','b','n'

        $spec = ['from' => $from, 'to' => $to];
        if ($promo) {
            $spec['promotion'] = $promo;
        }

        /** @var Move|null $move */
        $move = $chess->move($spec);
        if ($move === null) {
            throw new \InvalidArgumentException('illegal_move');
        }

        // SAN dispo directement (move() appelle moveToSAN())
        $san = $move->san ?? (string) $move;
        $fenAfter = $chess->fen();

        return ['fenAfter' => $fenAfter, 'san' => $san ?: strtoupper($uci)];
    }
}
