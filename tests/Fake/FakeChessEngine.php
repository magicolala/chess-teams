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
        // fabrique une "fen" de sortie prédictible pour les tests
        $fenAfter = $fen.'|'.$uci;
        $san = strtoupper($uci);

        return ['fenAfter' => $fenAfter, 'san' => $san];
    }
}
