<?php

namespace App\Application\Port;

/**
 * Abstraction du moteur d’échecs.
 * Retourne [ 'fenAfter' => string, 'san' => string|null ] si le coup est légal, sinon lève \InvalidArgumentException.
 */
interface ChessEngineInterface
{
    /**
     * @param string $fen FEN courant (ex: 'startpos' accepté pour démarrage)
     * @param string $uci Coup en UCI (ex: 'e2e4', 'g1f3', 'e7e8q'...)
     *
     * @return array{fenAfter: string, san?: string|null}
     */
    public function applyUci(string $fen, string $uci): array;
}
