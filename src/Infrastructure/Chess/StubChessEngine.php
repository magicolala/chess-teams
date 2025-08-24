<?php

namespace App\Infrastructure\Chess;

use App\Application\Port\ChessEngineInterface;

/**
 * Stub permissif: vérifie la forme de l'UCI et renvoie la même FEN (sans la changer).
 * À remplacer par une vraie lib (ex: php-chess) ultérieurement.
 */
final class StubChessEngine implements ChessEngineInterface
{
    public function applyUci(string $fen, string $uci): array
    {
        if (!preg_match('/^[a-h][1-8][a-h][1-8][qrbn]?$/i', $uci)) {
            throw new \InvalidArgumentException('invalid_uci');
        }
        $san = strtoupper($uci); // placeholder

        return ['fenAfter' => $fen, 'san' => $san];
    }
}
