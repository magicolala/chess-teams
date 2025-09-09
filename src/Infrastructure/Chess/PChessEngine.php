<?php

namespace App\Infrastructure\Chess;

use App\Application\Port\ChessEngineInterface;
use App\Infrastructure\Chess\ChesslablabEngine;

/**
 * Backward-compatible engine shim that mimics the former PChess engine
 * API while delegating to our ChesslablabEngine implementation.
 */
final class PChessEngine implements ChessEngineInterface
{
    private ChesslablabEngine $delegate;

    public function __construct()
    {
        $this->delegate = new ChesslablabEngine();
    }

    public function applyUci(string $fen, string $uci): array
    {
        // Delegate to the maintained engine which already normalizes 'startpos',
        // validates UCI, maps exceptions to InvalidArgumentException messages,
        // and returns ['fenAfter' => string, 'san' => string].
        return $this->delegate->applyUci($fen, $uci);
    }
}
