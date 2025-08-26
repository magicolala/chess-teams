<?php

namespace App\Application\Service;

use App\Entity\Game;
use App\Entity\Team;
use PChess\Chess\Board;
use PChess\Chess\Chess;

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
        $fen   = $game->getFen() === 'startpos' ? Board::DEFAULT_POSITION : $game->getFen();
        $chess = new Chess($fen);

        if ($chess->inCheckmate()) {
            // inCheckmate() s'applique au joueur au trait dans la FEN courante.
            // Or la FEN stockée est "après" le coup dans MakeMove, donc au trait = camp adverse.
            $loser      = $chess->turn; // 'w' ou 'b'
            $winnerTeam = ($loser === 'w') ? Team::NAME_B : Team::NAME_A;
            $result     = $winnerTeam.'#';

            $game->setStatus(Game::STATUS_FINISHED)->setResult($result);

            return ['isOver' => true, 'status' => $game->getStatus(), 'result' => $result];
        }

        if ($chess->inDraw()) {
            $result = '1/2-1/2';
            $game->setStatus(Game::STATUS_FINISHED)->setResult($result);

            return ['isOver' => true, 'status' => $game->getStatus(), 'result' => $result];
        }

        return ['isOver' => false, 'status' => $game->getStatus(), 'result' => $game->getResult()];
    }
}
