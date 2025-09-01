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
        // Vérifier si la partie a déjà un résultat (revendication de victoire, abandon, etc.)
        if (null !== $game->getResult()) {
            $game->setStatus(Game::STATUS_FINISHED);

            return ['isOver' => true, 'status' => Game::STATUS_FINISHED, 'result' => $game->getResult()];
        }

        $fen = 'startpos' === $game->getFen() ? PChessEngine::DEFAULT_FEN : $game->getFen();

        // Détection basique de fin de partie
        // Vérifier si c'est possiblement un mat basique (quelques cas simples)
        if ($this->isPossibleCheckmate($fen)) {
            $winnerTeam = $this->determineWinner($game->getTurnTeam());
            $result = $winnerTeam.'#'; // Ex: "A#" ou "B#"

            $game->setResult($result);
            $game->setStatus(Game::STATUS_FINISHED);

            return ['isOver' => true, 'status' => Game::STATUS_FINISHED, 'result' => $result];
        }

        // Vérifier la règle des 50 coups (approximative)
        if ($this->isPossibleDraw($fen)) {
            $result = '1/2-1/2';

            $game->setResult($result);
            $game->setStatus(Game::STATUS_FINISHED);

            return ['isOver' => true, 'status' => Game::STATUS_FINISHED, 'result' => $result];
        }

        return ['isOver' => false, 'status' => $game->getStatus(), 'result' => $game->getResult()];
    }

    /**
     * Détection très basique de mat potentiel
     * Dans une implémentation complète, ceci utiliserait un moteur d'échecs.
     */
    private function isPossibleCheckmate(string $fen): bool
    {
        // Pour cette implémentation basique, on assume qu'un mat ne se produit que rarement
        // Dans une vraie implémentation, on analyserait la position FEN
        // et on vérifierait tous les coups légaux possibles

        // Pour l'instant, retournons false - le mat sera détecté manuellement ou via revendication
        return false;
    }

    /**
     * Détection basique de nulle.
     */
    private function isPossibleDraw(string $fen): bool
    {
        $parts = explode(' ', $fen);
        if (count($parts) >= 5) {
            $halfmoveCounter = (int) ($parts[4] ?? 0);
            // Règle des 50 coups (100 demi-coups)
            if ($halfmoveCounter >= 100) {
                return true;
            }
        }

        return false;
    }

    /**
     * Détermine l'équipe gagnante quand l'autre est mat.
     */
    private function determineWinner(string $currentTurnTeam): string
    {
        // Si c'est le tour de A et qu'A est mat, alors B gagne
        return Game::TEAM_A === $currentTurnTeam ? Game::TEAM_B : Game::TEAM_A;
    }
}
