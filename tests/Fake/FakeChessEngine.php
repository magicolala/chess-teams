<?php

namespace App\Tests\Fake;

use App\Application\Port\ChessEngineInterface;

final class FakeChessEngine implements ChessEngineInterface
{
    public function applyUci(string $fen, string $uci): array
    {
        if (!preg_match('/^[a-h][1-8][a-h][1-8][qrbn]?$/i', $uci)) {
            throw new \InvalidArgumentException('invalid_uci');
        }

        // Debug
        error_log('FakeEngine - FEN reçu: '.$fen);
        error_log('FakeEngine - UCI reçu: '.$uci);

        $fenAfter = $fen.'|'.$uci;
        $san = strtoupper($uci);

        error_log('FakeEngine - FEN retourné: '.$fenAfter);

        return ['fenAfter' => $fenAfter, 'san' => $san];
    }
}
