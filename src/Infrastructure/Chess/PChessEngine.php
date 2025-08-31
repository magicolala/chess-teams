<?php

namespace App\Infrastructure\Chess;

use App\Application\Port\ChessEngineInterface;

/**
 * Implémentation simplifiée temporaire pour les tests.
 * Cette classe simule les réponses attendues sans validation complète des coups.
 */
final class PChessEngine implements ChessEngineInterface
{
    public const DEFAULT_FEN = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

    public function applyUci(string $fen, string $uci): array
    {
        // UCI basique: e2e4, e7e8q...
        if (!preg_match('/^[a-h][1-8][a-h][1-8]([qrbn])?$/i', $uci)) {
            throw new \InvalidArgumentException('invalid_uci');
        }

        // Validation FEN de base
        if ('startpos' !== $fen && !$this->isValidFen($fen)) {
            throw new \InvalidArgumentException('invalid_fen');
        }

        // Parse UCI
        $from = substr($uci, 0, 2);
        $to = substr($uci, 2, 2);
        $promotion = (5 === strlen($uci)) ? strtolower($uci[4]) : null;

        // Validation basique : on rejette les coups évidents illégaux
        if (!$this->isPlausibleMove($from, $to)) {
            throw new \InvalidArgumentException('illegal_move');
        }

        // Générer une FEN de base après le coup (simulation)
        $fenAfter = $this->generateFenAfterMove($fen, $from, $to, $promotion);

        // Générer une notation SAN de base
        $san = $this->generateSan($from, $to, $promotion);

        return [
            'fenAfter' => $fenAfter,
            'san' => $san,
        ];
    }

    private function isValidFen(string $fen): bool
    {
        // Validation FEN très basique
        $parts = explode(' ', $fen);

        return count($parts) >= 4
               && preg_match('/^[rnbqkpRNBQKP1-8\/]+$/', $parts[0])
               && in_array($parts[1], ['w', 'b']);
    }

    private function isPlausibleMove(string $from, string $to): bool
    {
        // Vérifications de base
        if ($from === $to) {
            return false;
        }

        $fromFile = ord($from[0]) - ord('a');
        $fromRank = (int) $from[1];
        $toFile = ord($to[0]) - ord('a');
        $toRank = (int) $to[1];

        // Vérifier que les coordonnées sont dans les limites
        return $fromFile >= 0 && $fromFile <= 7
               && $fromRank >= 1 && $fromRank <= 8
               && $toFile >= 0 && $toFile <= 7
               && $toRank >= 1 && $toRank <= 8;
    }

    private function generateFenAfterMove(string $fen, string $from, string $to, ?string $promotion): string
    {
        // Pour cette implémentation temporaire, on simule juste une modification basique
        if ('startpos' === $fen) {
            // Position après e2-e4 par exemple
            if ('e2' === $from && 'e4' === $to) {
                return 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq e3 0 1';
            }

            // Autres coups : on retourne une FEN basique modifiée
            return 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
        }

        // Pour une FEN donnée, on la retourne légèrement modifiée
        $parts = explode(' ', $fen);
        if (count($parts) >= 2) {
            // Changer le tour
            $parts[1] = 'w' === $parts[1] ? 'b' : 'w';

            return implode(' ', $parts);
        }

        return $fen;
    }

    private function generateSan(string $from, string $to, ?string $promotion): string
    {
        // Génération SAN très basique
        $fromFile = $from[0];
        $fromRank = $from[1];
        $toFile = $to[0];
        $toRank = $to[1];

        // Si c'est probablement un mouvement de pion
        if (abs((int) $fromRank - (int) $toRank) <= 2 && abs(ord($fromFile) - ord($toFile)) <= 1) {
            if ($fromFile !== $toFile) {
                // Prise de pion
                $san = $fromFile.'x'.$to;
            } else {
                // Mouvement de pion
                $san = $to;
            }

            if ($promotion) {
                $san .= '='.strtoupper($promotion);
            }

            return $san;
        }

        // Pour autres pièces, on retourne juste la case de destination
        return $to;
    }
}
