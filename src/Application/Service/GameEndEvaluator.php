<?php

namespace App\Application\Service;

use App\Entity\Game;
use App\Infrastructure\Chess\PChessEngine;

/**
 * Détecte et applique la fin de partie sur un Game :
 * - checkmate => vainqueur = camp qui vient de jouer (l'autre camp est mat)
 * - stalemate / draw => 1/2-1/2
 *
 * Retourne ['isOver'=>bool, 'status'=>..., 'result'=>...] ; et applique sur $game si fin.
 */
final class GameEndEvaluator
{
    public function evaluateAndApply(Game $game): array
    {
        // For now, we'll implement a basic game end detection
        // In a full implementation, you would use the chess engine to check for checkmate, stalemate, etc.

        $fen = 'startpos' === $game->getFen() ? PChessEngine::DEFAULT_FEN : $game->getFen();

        // Basic check - in a real implementation, you would need to analyze the FEN position
        // to determine if it's checkmate, stalemate, or ongoing
        // For now, we'll assume the game is ongoing (no immediate end conditions detected)

        // TODO: Implement proper game end detection logic:
        // - Parse FEN to determine current position
        // - Check for checkmate (king in check with no legal moves)
        // - Check for stalemate (no legal moves but king not in check)
        // - Check for draws (50-move rule, threefold repetition, insufficient material)

        return ['isOver' => false, 'status' => $game->getStatus(), 'result' => $game->getResult()];
    }
}
