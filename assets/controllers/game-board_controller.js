import { Controller } from '@hotwired/stimulus'
import $ from 'jquery'
import { Chess as ChessJs } from 'chess.js'

// Neo Chess Board inline implementation
const THEMES = {
  classic: {
    light: "#f0d9b5",
    dark: "#b58863",
    boardBorder: "#8b7355",
    whitePiece: "#ffffff",
    blackPiece: "#000000",
    pieceShadow: "rgba(0,0,0,0.3)",
    moveFrom: "#ffff0080",
    moveTo: "#ffff0080",
    lastMove: "#ffff0040",
    premove: "#00ff0040",
    dot: "#00000060",
    arrow: "#ff000080"
  },
  midnight: {
    light: "#3c3c41",
    dark: "#2a2a2e",
    boardBorder: "#1e1e22",
    whitePiece: "#f8f9fa",       // Blanc légèrement crème pour plus de douceur
    blackPiece: "#1a1a1a",       // Noir profond mais pas pur
    pieceShadow: "rgba(0,0,0,0.6)", // Ombre plus prononcée
    moveFrom: "#4a90e280",
    moveTo: "#4a90e280",
    lastMove: "#4a90e240",
    premove: "#00ff0040",
    dot: "#ffffff60",
    arrow: "#4a90e280"
  }
};

const FILES = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
const RANKS = ['1', '2', '3', '4', '5', '6', '7', '8'];

function sqToFR(square) {
  const f = square.charCodeAt(0) - 97;
  const r = parseInt(square[1]) - 1;
  return { f, r };
}

function sq(f, r) {
  return FILES[f] + RANKS[r];
}

function isWhitePiece(piece) {
  return piece === piece.toUpperCase();
}

function parseFEN(fen) {
  const chess = new ChessJs(fen);
  const board = Array(8).fill(null).map(() => Array(8).fill(null));

  for (let r = 0; r < 8; r++) {
    for (let f = 0; f < 8; f++) {
      const square = FILES[f] + RANKS[r];
      const piece = chess.get(square);
      if (piece) {
        board[r][f] = piece.color === 'w' ? piece.type.toUpperCase() : piece.type.toLowerCase();
      }
    }
  }

  // Extract fields from FEN directly to avoid relying on chess.js internals
  // FEN format: pieces activeColor castling enPassant halfmove fullmove
  const fenString = typeof chess.fen === 'function' ? chess.fen() : (fen || '');
  const parts = fenString.split(' ');
  const castling = parts[2] && parts[2].length > 0 ? parts[2] : '-';
  const ep = parts[3] && parts[3] !== '-' ? parts[3] : '-';
  const halfmove = parts[4] ? parseInt(parts[4], 10) : 0;
  const fullmove = parts[5] ? parseInt(parts[5], 10) : 1;

  return {
    board,
    turn: chess.turn(),
    castling,
    ep,
    halfmove,
    fullmove
  };
}

// FlatSprites class - identical to Neo Chess Board Ts Library
class FlatSprites {
  constructor(size, colors) {
    this.size = size;
    this.colors = colors;
    this.sheet = this.build(size);
  }
  
  getSheet() {
    return this.sheet;
  }
  
  // Rounded rectangle helper
  rr(ctx, x, y, w, h, r) {
    const rr = Math.min(r, w / 2, h / 2);
    ctx.beginPath();
    ctx.moveTo(x + rr, y);
    ctx.lineTo(x + w - rr, y);
    ctx.quadraticCurveTo(x + w, y, x + w, y + rr);
    ctx.lineTo(x + w, y + h - rr);
    ctx.quadraticCurveTo(x + w, y + h, x + w - rr, y + h);
    ctx.lineTo(x + rr, y + h);
    ctx.quadraticCurveTo(x, y + h, x, y + h - rr);
    ctx.lineTo(x, y + rr);
    ctx.quadraticCurveTo(x, y, x + rr, y);
    ctx.closePath();
  }
  
  build(px) {
    const c = document.createElement('canvas');
    c.width = px * 6;
    c.height = px * 2;
    const ctx = c.getContext('2d');
    const order = ['k', 'q', 'r', 'b', 'n', 'p'];
    order.forEach((t, i) => {
      this.draw(ctx, i * px, 0, px, t, 'black');
      this.draw(ctx, i * px, px, px, t, 'white');
    });
    return c;
  }
  
  draw(ctx, x, y, s, type, color) {
    const C = color === 'white' ? this.colors.whitePiece : this.colors.blackPiece;
    const S = this.colors.pieceShadow;
    
    ctx.save();
    ctx.translate(x, y);
    
    // Draw shadow
    ctx.fillStyle = S;
    ctx.beginPath();
    ctx.ellipse(s * 0.5, s * 0.68, s * 0.28, s * 0.1, 0, 0, Math.PI * 2);
    ctx.fill();
    
    ctx.fillStyle = C;
    ctx.lineJoin = 'round';
    ctx.lineCap = 'round';
    
    // Base for most pieces
    const base = () => {
      ctx.beginPath();
      ctx.moveTo(s * 0.2, s * 0.7);
      ctx.quadraticCurveTo(s * 0.5, s * 0.6, s * 0.8, s * 0.7);
      ctx.lineTo(s * 0.8, s * 0.8);
      ctx.quadraticCurveTo(s * 0.5, s * 0.85, s * 0.2, s * 0.8);
      ctx.closePath();
      ctx.fill();
    };
    
    // Draw pieces based on type
    if (type === 'p') {
      // Pawn - head
      ctx.beginPath();
      ctx.arc(s * 0.5, s * 0.38, s * 0.12, 0, Math.PI * 2);
      ctx.fill();
      // Pawn - body
      ctx.beginPath();
      ctx.moveTo(s * 0.38, s * 0.52);
      ctx.quadraticCurveTo(s * 0.5, s * 0.42, s * 0.62, s * 0.52);
      ctx.quadraticCurveTo(s * 0.64, s * 0.6, s * 0.5, s * 0.62);
      ctx.quadraticCurveTo(s * 0.36, s * 0.6, s * 0.38, s * 0.52);
      ctx.closePath();
      ctx.fill();
      base();
    }
    
    if (type === 'r') {
      // Rook - tower
      ctx.beginPath();
      this.rr(ctx, s * 0.32, s * 0.3, s * 0.36, s * 0.34, s * 0.04);
      ctx.fill();
      // Rook - crenellations
      ctx.beginPath();
      this.rr(ctx, s * 0.3, s * 0.22, s * 0.12, s * 0.1, s * 0.02);
      ctx.fill();
      ctx.beginPath();
      this.rr(ctx, s * 0.44, s * 0.2, s * 0.12, s * 0.12, s * 0.02);
      ctx.fill();
      ctx.beginPath();
      this.rr(ctx, s * 0.58, s * 0.22, s * 0.12, s * 0.1, s * 0.02);
      ctx.fill();
      base();
    }
    
    if (type === 'n') {
      // Knight - horse head
      ctx.beginPath();
      ctx.moveTo(s * 0.64, s * 0.6);
      ctx.quadraticCurveTo(s * 0.7, s * 0.35, s * 0.54, s * 0.28);
      ctx.quadraticCurveTo(s * 0.46, s * 0.24, s * 0.44, s * 0.3);
      ctx.quadraticCurveTo(s * 0.42, s * 0.42, s * 0.34, s * 0.44);
      ctx.quadraticCurveTo(s * 0.3, s * 0.46, s * 0.28, s * 0.5);
      ctx.quadraticCurveTo(s * 0.26, s * 0.6, s * 0.38, s * 0.62);
      ctx.closePath();
      ctx.fill();
      // Knight - eye
      const C = ctx.fillStyle;
      ctx.fillStyle = 'rgba(0,0,0,0.15)';
      ctx.beginPath();
      ctx.arc(s * 0.5, s * 0.36, s * 0.02, 0, Math.PI * 2);
      ctx.fill();
      ctx.fillStyle = C;
      base();
    }
    
    if (type === 'b') {
      // Bishop - mitre
      ctx.beginPath();
      ctx.ellipse(s * 0.5, s * 0.42, s * 0.12, s * 0.18, 0, 0, Math.PI * 2);
      ctx.fill();
      // Bishop - slit
      const C = ctx.globalCompositeOperation;
      ctx.globalCompositeOperation = 'destination-out';
      ctx.beginPath();
      ctx.moveTo(s * 0.5, s * 0.28);
      ctx.lineTo(s * 0.5, s * 0.52);
      ctx.lineWidth = s * 0.04;
      ctx.stroke();
      ctx.globalCompositeOperation = C;
      base();
    }
    
    if (type === 'q') {
      // Queen - crown
      ctx.beginPath();
      ctx.moveTo(s * 0.3, s * 0.3);
      ctx.lineTo(s * 0.4, s * 0.18);
      ctx.lineTo(s * 0.5, s * 0.3);
      ctx.lineTo(s * 0.6, s * 0.18);
      ctx.lineTo(s * 0.7, s * 0.3);
      ctx.closePath();
      ctx.fill();
      // Queen - body
      ctx.beginPath();
      ctx.ellipse(s * 0.5, s * 0.5, s * 0.16, s * 0.16, 0, 0, Math.PI * 2);
      ctx.fill();
      base();
    }
    
    if (type === 'k') {
      // King - cross
      ctx.beginPath();
      this.rr(ctx, s * 0.47, s * 0.16, s * 0.06, s * 0.16, s * 0.02);
      ctx.fill();
      ctx.beginPath();
      this.rr(ctx, s * 0.4, s * 0.22, s * 0.2, s * 0.06, s * 0.02);
      ctx.fill();
      // King - crown
      ctx.beginPath();
      this.rr(ctx, s * 0.36, s * 0.34, s * 0.28, s * 0.26, s * 0.08);
      ctx.fill();
      base();
    }
    
    ctx.restore();
  }
}

class SimpleNeoChessBoard {
  constructor(element, options = {}) {
    this.element = element;
    this.bus = { listeners: {} };
    this.theme = THEMES[options.theme || 'classic'];
    this.orientation = options.orientation || 'white';
    this.interactive = options.interactive !== false;
    this.showCoordinates = options.showCoordinates || false;
    
    this.sizePx = 480;
    this.squareSize = 60;
    
    this.gamePosition = options.fen || 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
    this.state = parseFEN(this.gamePosition);
    this.selected = null;
    this.lastMove = null;
    this.dragging = null;
    this.hoverSquare = null;
    
    // Initialize piece sprites like in Neo Chess Board Ts Library
    this.sprites = new FlatSprites(128, this.theme);

    // Bind stable event handler refs to allow proper add/removeEventListener
    this._onPointerDown = this.onPointerDown.bind(this);
    this._onPointerMove = this.onPointerMove.bind(this);
    this._onPointerUp = this.onPointerUp.bind(this);
    this._eventsAttached = false;
    
    this.initBoard();
    this.attachEvents();
  }

  on(event, handler) {
    if (!this.bus.listeners[event]) {
      this.bus.listeners[event] = [];
    }
    this.bus.listeners[event].push(handler);
  }

  emit(event, data) {
    if (this.bus.listeners[event]) {
      this.bus.listeners[event].forEach(handler => handler(data));
    }
  }

  initBoard() {
    this.element.innerHTML = '';
    this.element.style.position = 'relative';
    this.element.style.width = '100%';
    this.element.style.height = '100%';
    this.element.style.aspectRatio = '1/1';
    this.element.style.userSelect = 'none';
    this.element.style.borderRadius = '8px';
    this.element.style.overflow = 'hidden';
    this.element.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    this.element.style.backgroundColor = this.theme.boardBorder;
    
    this.canvas = document.createElement('canvas');
    this.canvas.style.width = '100%';
    this.canvas.style.height = '100%';
    this.canvas.style.display = 'block';
    this.element.appendChild(this.canvas);
    
    this.ctx = this.canvas.getContext('2d');
    this.resize();
    this.render();
    
    if (typeof ResizeObserver !== 'undefined') {
      this.resizeObserver = new ResizeObserver(() => this.resize());
      this.resizeObserver.observe(this.element);
    }
  }

  resize() {
    const rect = this.element.getBoundingClientRect();
    const size = Math.min(rect.width, rect.height) || 480;
    const dpr = window.devicePixelRatio || 1;
    
    this.canvas.width = size * dpr;
    this.canvas.height = size * dpr;
    this.canvas.style.width = size + 'px';
    this.canvas.style.height = size + 'px';
    this.ctx.scale(dpr, dpr);
    
    this.sizePx = size;
    this.squareSize = size / 8;
    this.render();
  }

  render() {
    if (!this.ctx) return;
    this.ctx.clearRect(0, 0, this.sizePx, this.sizePx);
    this.drawBoard();
    this.drawHighlights();
    this.drawPieces();
  }

  drawBoard() {
    const ctx = this.ctx;
    const squareSize = this.squareSize;
    
    ctx.fillStyle = this.theme.boardBorder;
    ctx.fillRect(0, 0, this.sizePx, this.sizePx);
    
    for (let rank = 0; rank < 8; rank++) {
      for (let file = 0; file < 8; file++) {
        const x = file * squareSize;
        const y = (this.orientation === 'white' ? 7 - rank : rank) * squareSize;
        ctx.fillStyle = (rank + file) % 2 === 0 ? this.theme.light : this.theme.dark;
        ctx.fillRect(x, y, squareSize, squareSize);
      }
    }
    
    if (this.showCoordinates) {
      this.drawCoordinates();
    }
  }

  drawCoordinates() {
    const ctx = this.ctx;
    const squareSize = this.squareSize;
    
    ctx.save();
    ctx.font = `${Math.floor(squareSize * 0.15)}px Arial, sans-serif`;
    ctx.fillStyle = 'rgba(255,255,255,0.8)';
    
    ctx.textAlign = 'left';
    ctx.textBaseline = 'bottom';
    for (let f = 0; f < 8; f++) {
      const file = this.orientation === 'white' ? FILES[f] : FILES[7 - f];
      const x = f * squareSize + 4;
      const y = this.sizePx - 4;
      ctx.fillText(file, x, y);
    }
    
    ctx.textAlign = 'right';
    ctx.textBaseline = 'top';
    for (let r = 0; r < 8; r++) {
      const rank = this.orientation === 'white' ? RANKS[7 - r] : RANKS[r];
      const x = this.sizePx - 4;
      const y = r * squareSize + 4;
      ctx.fillText(rank, x, y);
    }
    
    ctx.restore();
  }

  drawHighlights() {
    const ctx = this.ctx;
    const squareSize = this.squareSize;
    
    if (this.lastMove) {
      ctx.fillStyle = this.theme.lastMove;
      const fromPos = this.squareToXY(this.lastMove.from);
      const toPos = this.squareToXY(this.lastMove.to);
      ctx.fillRect(fromPos.x, fromPos.y, squareSize, squareSize);
      ctx.fillRect(toPos.x, toPos.y, squareSize, squareSize);
    }
    
    if (this.selected) {
      ctx.fillStyle = this.theme.moveFrom;
      const pos = this.squareToXY(this.selected);
      ctx.fillRect(pos.x, pos.y, squareSize, squareSize);
    }
    
    if (this.hoverSquare && this.dragging) {
      ctx.fillStyle = this.theme.moveTo;
      const pos = this.squareToXY(this.hoverSquare);
      ctx.fillRect(pos.x, pos.y, squareSize, squareSize);
    }
  }

  drawPieces() {
    const ctx = this.ctx;
    const squareSize = this.squareSize;
    
    // Draw pieces using geometric sprites like Neo Chess Board Ts Library
    for (let rank = 0; rank < 8; rank++) {
      for (let file = 0; file < 8; file++) {
        const piece = this.state.board[rank][file];
        if (!piece) continue;
        
        const square = sq(file, rank);
        if (this.dragging && this.dragging.from === square) continue;
        
        const pos = this.squareToXY(square);
        this.drawPieceSprite(piece, pos.x, pos.y, 1);
      }
    }
    
    // Draw dragging piece with slightly larger scale
    if (this.dragging) {
      const piece = this.dragging.piece;
      const x = this.dragging.x - squareSize / 2;
      const y = this.dragging.y - squareSize / 2;
      this.drawPieceSprite(piece, x, y, 1.05);
    }
  }
  
  drawPieceSprite(piece, x, y, scale = 1) {
    // Piece mapping: k=0, q=1, r=2, b=3, n=4, p=5
    const map = { k: 0, q: 1, r: 2, b: 3, n: 4, p: 5 };
    const isWhite = isWhitePiece(piece);
    const pieceIndex = map[piece.toLowerCase()];
    
    if (pieceIndex === undefined) return;
    
    const spriteSize = 128; // Size used in sprite sheet
    const sourceX = pieceIndex * spriteSize;
    const sourceY = isWhite ? spriteSize : 0; // White pieces in bottom row, black in top
    
    const destSize = this.squareSize * scale;
    const destX = x + (this.squareSize - destSize) / 2;
    const destY = y + (this.squareSize - destSize) / 2;
    
    // Draw the piece sprite from the sprite sheet
    this.ctx.drawImage(
      this.sprites.getSheet(),
      sourceX, sourceY, spriteSize, spriteSize, // Source rectangle
      destX, destY, destSize, destSize // Destination rectangle
    );
  }

  squareToXY(square) {
    const { f, r } = sqToFR(square);
    const x = (this.orientation === 'white' ? f : 7 - f) * this.squareSize;
    const y = (this.orientation === 'white' ? 7 - r : r) * this.squareSize;
    return { x, y };
  }

  xyToSquare(x, y) {
    const file = Math.floor(x / this.squareSize);
    const rank = Math.floor(y / this.squareSize);
    
    if (file < 0 || file > 7 || rank < 0 || rank > 7) return null;
    
    const actualFile = this.orientation === 'white' ? file : 7 - file;
    const actualRank = this.orientation === 'white' ? 7 - rank : rank;
    
    return sq(actualFile, actualRank);
  }

  attachEvents() {
    if (!this.canvas) return;
    // Attach only once and only if interactive
    if (!this.interactive || this._eventsAttached) return;

    this.canvas.addEventListener('pointerdown', this._onPointerDown);
    this.canvas.addEventListener('pointermove', this._onPointerMove);
    this.canvas.addEventListener('pointerup', this._onPointerUp);
    this.canvas.addEventListener('contextmenu', (e) => e.preventDefault());
    this._eventsAttached = true;
  }

  detachEvents() {
    if (!this.canvas || !this._eventsAttached) return;
    this.canvas.removeEventListener('pointerdown', this._onPointerDown);
    this.canvas.removeEventListener('pointermove', this._onPointerMove);
    this.canvas.removeEventListener('pointerup', this._onPointerUp);
    this._eventsAttached = false;
  }

  getEventPos(e) {
    const rect = this.canvas.getBoundingClientRect();
    return {
      x: (e.clientX - rect.left) * (this.sizePx / rect.width),
      y: (e.clientY - rect.top) * (this.sizePx / rect.height)
    };
  }

  onPointerDown(e) {
    e.preventDefault();
    const pos = this.getEventPos(e);
    const square = this.xyToSquare(pos.x, pos.y);
    if (!square) return;
    
    const piece = this.state.board[sqToFR(square).r][sqToFR(square).f];
    if (!piece) return;
    
    this.selected = square;
    this.dragging = { from: square, piece: piece, x: pos.x, y: pos.y };
    this.render();
  }

  onPointerMove(e) {
    if (!this.dragging) return;
    
    const pos = this.getEventPos(e);
    this.dragging.x = pos.x;
    this.dragging.y = pos.y;
    
    const square = this.xyToSquare(pos.x, pos.y);
    this.hoverSquare = square;
    this.render();
  }

  onPointerUp(e) {
    if (!this.dragging) return;
    
    const pos = this.getEventPos(e);
    const toSquare = this.xyToSquare(pos.x, pos.y);
    
    if (toSquare && toSquare !== this.dragging.from) {
      this.makeMove(this.dragging.from, toSquare);
    }
    
    this.selected = null;
    this.dragging = null;
    this.hoverSquare = null;
    this.render();
  }

  makeMove(from, to) {
    const fromPos = sqToFR(from);
    const piece = this.state.board[fromPos.r][fromPos.f];
    if (!piece) {
      this.emit('illegal', { from, to, reason: 'No piece on source square' });
      return;
    }
    
    // NE PAS modifier l'état du board ici - laisser le contrôleur Stimulus valider d'abord
    // L'état sera mis à jour par setPosition() si le coup est accepté par le serveur
    this.emit('move', { from, to, fen: this.gamePosition });
  }

  generateFEN() {
    let fen = '';
    for (let rank = 7; rank >= 0; rank--) {
      let emptyCount = 0;
      for (let file = 0; file < 8; file++) {
        const piece = this.state.board[rank][file];
        if (piece) {
          if (emptyCount > 0) {
            fen += emptyCount;
            emptyCount = 0;
          }
          fen += piece;
        } else {
          emptyCount++;
        }
      }
      if (emptyCount > 0) fen += emptyCount;
      if (rank > 0) fen += '/';
    }
    
    fen += ' ' + this.state.turn;
    fen += ' ' + this.state.castling;
    fen += ' ' + (this.state.ep || '-');
    fen += ' ' + this.state.halfmove;
    fen += ' ' + this.state.fullmove;
    
    return fen;
  }

  setPosition(fen, immediate = false) {
    this.gamePosition = fen;
    this.state = parseFEN(fen);
    this.lastMove = null;
    this.selected = null;
    this.dragging = null;
    this.hoverSquare = null;
    this.render();
    this.emit('update', { fen });
  }

  getPosition() {
    return this.gamePosition;
  }

  setInteractive(interactive) {
    this.interactive = interactive;
    // Properly toggle listeners
    if (this.interactive) {
      this.attachEvents();
    } else {
      this.detachEvents();
    }
  }

  destroy() {
    if (this.resizeObserver) {
      this.resizeObserver.disconnect();
    }
    this.detachEvents();
    this.element.innerHTML = '';
  }
}

export default class extends Controller {
    static values = { fen: String, gameId: String, turnTeam: String, deadlineTs: Number, status: String }
    static targets = ['timer', 'turnTeam', 'status', 'result', 'timeoutDecision']

    connect() {
        console.debug('[game-board] connect() with Neo Chess Board (inline)', {
            fen: this.fenValue, gameId: this.gameIdValue, turnTeam: this.turnTeamValue,
            deadlineTs: this.deadlineTsValue, status: this.statusValue
        })

        const boardEl = this.element.querySelector('#board')
        if (!boardEl) {
            console.error('[game-board] #board introuvable dans l\'élément du contrôleur', this.element)
            this.printDebug('❌ #board introuvable. Vérifie l\'id="board" dans le HTML.')
            return
        }

        this.chessJs = new ChessJs(this.fenValue === 'startpos' ? undefined : this.fenValue)

        try {
            // Configuration du Neo Chess Board
            // Déterminer l'orientation de l'échiquier selon la couleur du joueur
            const playerColor = this.getPlayerColor()
            const isPlayerTurn = this.isCurrentPlayerTurn()
            
            // Au démarrage, si c'est mon tour et la partie est live, ne pas autoriser l'interaction tant que "Prêt" n'est pas cliqué
            const initialTurnReady = false
            this.board = new SimpleNeoChessBoard(boardEl, {
                fen: this.fenValue === 'startpos' ? undefined : this.fenValue,
                theme: 'midnight',
                interactive: this.statusValue === 'live' && isPlayerTurn && initialTurnReady, // Interactif seulement si c'est le tour du joueur ET prêt
                showCoordinates: true,
                orientation: playerColor
            })

            // Masquer immédiatement le canvas si c'est mon tour et que je ne suis pas encore "Prêt"
            if (this.statusValue === 'live' && isPlayerTurn) {
                const readyNow = this.isTurnReady?.() || false
                if (!readyNow) {
                    const canvas = boardEl.querySelector('canvas')
                    if (canvas) canvas.style.visibility = 'hidden'
                }
            }
            
            // Ajouter overlay de grille si ce n'est pas le tour du joueur
            this.setupBoardOverlay(isPlayerTurn)

            // Écouteurs d'événements Neo Chess Board
            this.board.on('move', ({ from, to, fen }) => {
                console.debug('[game-board] Neo Chess Board move event', { from, to, fen })
                this.onNeoMove(from, to)
            })

            this.board.on('illegal', ({ from, to, reason }) => {
                console.debug('[game-board] Neo Chess Board illegal move', { from, to, reason })
                this.printDebug(`❌ Coup illégal: ${from}-${to} (${reason})`)
            })

            console.debug('[game-board] Neo Chess Board prêt', this.board)
            this.printDebug('✅ Neo Chess Board initialisé (inline)')
        } catch (e) {
            console.error('[game-board] échec init Neo Chess Board', e)
            this.printDebug('❌ Erreur init Neo Chess Board: ' + e?.message)
            return
        }

        // Écouter les événements du contrôleur game-poll (même élément)
        this._onFenUpdated = (e) => this.onPollFenUpdated(e)
        this._onGameUpdated = (e) => this.onPollGameUpdated(e)
        this.element.addEventListener('game-poll:fenUpdated', this._onFenUpdated)
        this.element.addEventListener('game-poll:gameUpdated', this._onGameUpdated)

        // État pour la validation manuelle des coups
        this._pending = null // { from, to, uci, prevBoardFen, prevGameFen }
        // Garde pour éviter les doubles envois
        this._submittingMove = false
        this._ensurePendingControls()

        // Timer
        this.timerInterval = setInterval(() => this.tickTimer(), 250)
        this.renderState()

        // Gate "Prêt" par tour: on stocke l'état 'ready' par (gameId, ply)
        this.currentPly = null
        this.turnReady = false
    }

    disconnect() {
        clearInterval(this.timerInterval)
        this.board?.destroy?.()
        // Nettoyer les listeners
        if (this._onFenUpdated) this.element.removeEventListener('game-poll:fenUpdated', this._onFenUpdated)
        if (this._onGameUpdated) this.element.removeEventListener('game-poll:gameUpdated', this._onGameUpdated)
        console.debug('[game-board] disconnect()')
    }

    printDebug(line) {
        let box = this.element.querySelector('.debug-box')
        if (!box) {
            box = document.createElement('div')
            box.className = 'debug-box'
            box.innerHTML = `<strong>DEBUG board</strong><pre></pre>`
            this.element.appendChild(box)
        }
        const pre = box.querySelector('pre')
        pre.textContent += (pre.textContent ? '\n' : '') + line
    }

    renderState() {
        if (this.hasTurnTeamTarget) this.turnTeamTarget.textContent = this.turnTeamValue
        if (this.hasStatusTarget) this.statusTarget.textContent = this.statusValue
    }

    tickTimer() {
        if (!this.deadlineTsValue || this.statusValue === 'finished') {
            if (this.hasTimerTarget) {
                this.timerTarget.textContent = '-'
                this.timerTarget.classList.remove('chess-timer-urgent')
            }
            return
        }
        const remain = Math.max(0, Math.floor((this.deadlineTsValue - Date.now()) / 1000))
        if (this.hasTimerTarget) {
            this.timerTarget.textContent = remain + 's'
            
            // Ajouter un feedback visuel pour les dernières 30 secondes
            if (remain <= 30 && remain > 0) {
                this.timerTarget.classList.add('chess-timer-urgent')
            } else {
                this.timerTarget.classList.remove('chess-timer-urgent')
            }
        }
    }

    // Gestionnaire pour les coups Neo Chess Board
    async onNeoMove(from, to) {
        // Ne permettre de bouger que si la partie est en cours
        if (this.statusValue !== 'live') {
            this.printDebug(`❌ Partie pas en cours (${this.statusValue}), coup rejeté`)
            return
        }

        // Bloquer toute tentative si un coup est déjà en attente ou en cours de soumission
        if (this._pending || this._submittingMove) {
            this.printDebug('⏳ Un coup est déjà en attente/validation. Annulez ou attendez la réponse du serveur.')
            return
        }

        // Sauvegarder les positions originales (pour annuler au besoin)
        const originalPos = this.chessJs.fen()
        const originalBoardPos = this.board.getPosition()
        
        // Vérifier si c'est un coup légal avec chess.js aussi
        // Détecter si c'est un coup de promotion (pion à la 7e rangée qui va à la 8e)
        const isPotentialPromotion = () => {
            const piece = this.chessJs.get(from);
            return piece && piece.type === 'p' && 
                   ((piece.color === 'w' && from[1] === '7' && to[1] === '8') ||
                    (piece.color === 'b' && from[1] === '2' && to[1] === '1'));
        };

        // N'ajouter la promotion que si c'est nécessaire
        const moveOptions = {
            from: from,
            to: to
        };
        
        if (isPotentialPromotion()) {
            moveOptions.promotion = 'q'; // Promouvoir en dame si c'est une promotion
        }
        
        let move = null
        try {
            move = this.chessJs.move(moveOptions)
        } catch (err) {
            console.warn('[game-board] Exception chess.js.move:', err, moveOptions)
            this.printDebug(`❌ Coup invalide (exception): ${from}-${to}`)
            // Remettre la position sur le board Neo
            this.board.setPosition(originalBoardPos, true)
            return
        }

        // Coup illégal - revenir à la position d'origine
        if (move === null) {
            console.warn('[game-board] Coup rejeté par chess.js:', from, to)
            this.printDebug(`❌ Coup rejeté par chess.js: ${from}-${to}`)
            // Remettre la position sur le board Neo
            this.board.setPosition(originalBoardPos, true)
            return
        }

        // Coup légal localement - NE PAS envoyer directement.
        // Préparer une validation manuelle: prévisualiser le coup et afficher les contrôles Valider/Annuler
        const uci = move.from + move.to + (move.promotion || '')
        this._pending = {
            from,
            to,
            uci,
            prevBoardFen: originalBoardPos,
            prevGameFen: originalPos
        }

        // Appliquer visuellement le coup sur le canvas et sur chess.js
        // (chessJs a déjà move(moveOptions) réussi)
        const previewFen = this.chessJs.fen()
        this.board.setPosition(previewFen, true)
        this._showPendingControls(uci)
        this.printDebug(`📝 Coup en attente de validation: ${uci}`)
        // Tant que l'utilisateur n'a pas validé/annulé, empêcher de jouer un autre coup
        this.board.setInteractive(false)
    }

    async offerMove(e) {
        const uci = e.currentTarget.dataset.uci
        console.debug('[game-board] offerMove', uci)
        await this.sendMove(uci)
    }

    async tick() {
        console.debug('[game-board] tick()')
        await this.apiPost(`/games/${this.gameIdValue}/tick`, {})
    }

    async sendMove(uci) {
        // Éviter les doubles envois si un submit est déjà en cours
        if (this._submittingMove) {
            console.debug('[game-board] sendMove ignoré (soumission déjà en cours)')
            return false
        }
        this._submittingMove = true
        this._setPendingDisabled(true)
        // Empêcher toute interaction pendant l'envoi au serveur
        this.board.setInteractive(false)
        try {
            const ok = await this.apiPost(`/games/${this.gameIdValue}/move`, { uci })
            if (!ok) { 
                this.printDebug('❌ Move refusé par le serveur')
                return false
            }
            
            const g = await this.fetchGame()
            console.debug('[game-board] state after move', g)
            
            this.fenValue = g.fen
            this.turnTeamValue = g.turnTeam
            this.deadlineTsValue = g.turnDeadline || 0
            this.statusValue = g.status
            
            this.chessJs.load(g.fen === 'startpos' ? 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1' : g.fen)
            
            this.board.setPosition(g.fen === 'startpos' ? 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1' : g.fen)
            
            await this.reloadMoves()
            this.printDebug('✅ Move OK, FEN mise à jour')
            return true
        } finally {
            // Réactiver en cas d'échec; en cas de succès, les contrôles sont masqués plus loin
            this._submittingMove = false
            this._setPendingDisabled(false)
            // Si l'envoi a échoué, on pourra réactiver l'interaction plus tard (dans confirmPending on le gère)
        }
    }

    // ----- Validation manuelle des coups -----
    _ensurePendingControls() {
        // Crée dynamiquement une barre d'actions si absente
        let actions = this.element.querySelector('.game-actions')
        if (!actions) return // Pas critique, on n'affiche pas les contrôles

        let pending = this.element.querySelector('.pending-move-controls')
        if (!pending) {
            pending = document.createElement('div')
            pending.className = 'pending-move-controls'
            pending.style.display = 'none'
            pending.style.gap = '0.5rem'
            pending.style.marginTop = '0.25rem'
            pending.innerHTML = `
                <span class="neo-text-sm">Coup proposé: <code class="pending-uci"></code></span>
                <button class="neo-btn neo-btn-success neo-btn-sm" data-action="game-board#confirmPending">✔️ Valider</button>
                <button class="neo-btn neo-btn-secondary neo-btn-sm" data-action="game-board#cancelPending">✖️ Annuler</button>
            `
            actions.parentNode.insertBefore(pending, actions.nextSibling)
        }
        this._pendingEl = pending
        this._pendingUciEl = pending.querySelector('.pending-uci')
    }

    _showPendingControls(uci) {
        if (!this._pendingEl) this._ensurePendingControls()
        if (this._pendingUciEl) this._pendingUciEl.textContent = uci
        if (this._pendingEl) this._pendingEl.style.display = ''
    }

    _hidePendingControls() {
        if (this._pendingEl) this._pendingEl.style.display = 'none'
        if (this._pendingUciEl) this._pendingUciEl.textContent = ''
    }

    // Active/désactive les contrôles de coup en attente afin d'éviter les doubles clics
    _setPendingDisabled(disabled) {
        if (!this._pendingEl) return
        const btns = this._pendingEl.querySelectorAll('button')
        btns.forEach(b => { b.disabled = !!disabled })
    }

    _showStatusOverlay(message, icon, spinner) {
        const boardEl = this.element.querySelector('#board')
        if (!boardEl) return
        
        // Supprimer l'overlay existant s'il y en a un
        const existingOverlay = boardEl.querySelector('.board-overlay')
        if (existingOverlay) {
            existingOverlay.remove()
        }
        
        const overlay = document.createElement('div')
        overlay.className = 'board-overlay'
        overlay.innerHTML = `
            <div class="overlay-content">
                <div class="waiting-message">
                    <i class="material-icons">${icon}</i>
                    <h3>${message}</h3>
                    ${spinner ? '<div class="spinner-border text-light" role="status"></div>' : ''}
                </div>
            </div>
        `
        
        // Styles pour l'overlay
        overlay.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: 8px;
            backdrop-filter: blur(2px);
        `
        // Styliser le contenu et l'icône
        const content = overlay.querySelector('.overlay-content')
        if (content) {
            content.style.cssText = `
                text-align: center;
                color: white;
                padding: 2rem;
            `
        }
        const iconEl = overlay.querySelector('.material-icons')
        if (iconEl) {
            iconEl.style.cssText = `
                font-size: 3rem;
                margin-bottom: 1rem;
                opacity: 0.9;
                ${spinner ? 'animation: spin 1.2s linear infinite;' : ''}
            `
        }
        // Ajouter l'animation de rotation si absente
        if (spinner && !document.querySelector('#board-overlay-styles')) {
            const style = document.createElement('style')
            style.id = 'board-overlay-styles'
            style.textContent = `
                @keyframes spin { 0% { transform: rotate(0deg);} 100% { transform: rotate(360deg);} }
            `
            document.head.appendChild(style)
        }
        
        boardEl.appendChild(overlay)
    }

    _hideStatusOverlay() {
        const boardEl = this.element.querySelector('#board')
        if (!boardEl) return
        
        const existingOverlay = boardEl.querySelector('.board-overlay')
        if (existingOverlay) {
            existingOverlay.remove()
        }
    }

    async confirmPending() {
        if (!this._pending) return
        const { uci } = this._pending
        this.printDebug(`✅ Validation du coup: ${uci}`)
        // Afficher un loader pendant la validation côté serveur
        this._showStatusOverlay('Validation du coup…', 'autorenew', true)
        const ok = await this.sendMove(uci)
        if (!ok) {
            // Revenir à l'état précédent si le serveur refuse
            this.chessJs.load(this._pending.prevGameFen)
            this.board.setPosition(this._pending.prevBoardFen, true)
            this.printDebug('↩️ Retour à la position précédente (move refusé)')
            // Retirer le loader et réactiver si c'est toujours mon tour et prêt
            this._hideStatusOverlay()
            const canInteract = this.statusValue === 'live' && this.isCurrentPlayerTurn() && this.isTurnReady()
            this.board.setInteractive(!!canInteract)
        } else {
            // Garder un overlay d'attente côté client jusqu'à la MAJ serveur
            this._hidePendingControls()
            this._pending = null
            // Désactiver l'interaction immédiatement côté client
            this.board.setInteractive(false)
            // Mettre à jour le message pour indiquer l'attente de l'adversaire
            this._showStatusOverlay(`En attente de l'adversaire…`, 'hourglass_empty', true)
            // Masquer le canvas en attendant pour éviter toute confusion visuelle
            const boardEl = this.element.querySelector('#board')
            const canvas = boardEl?.querySelector('canvas')
            if (canvas) canvas.style.visibility = 'hidden'
            this.printDebug('✅ Coup envoyé. En attente de l\'adversaire…')
        }
        this._pending = null
        this._hidePendingControls()
    }

    cancelPending() {
        if (!this._pending) return
        this.printDebug(`⛔ Annulation du coup: ${this._pending.uci}`)
        // Restaurer les positions d'origine
        this.chessJs.load(this._pending.prevGameFen)
        this.board.setPosition(this._pending.prevBoardFen, true)
        this._pending = null
        this._hidePendingControls()
        // Redonner la main pour permettre de rejouer un autre coup
        // Uniquement si c'est toujours mon tour et que je suis prêt
        const canInteract = this.statusValue === 'live' && this.isCurrentPlayerTurn() && this.isTurnReady()
        this.board.setInteractive(!!canInteract)
    }

    // ----- Réactions aux événements du polling -----
    onPollFenUpdated(event) {
        const fen = event?.detail?.fen
        if (!fen) return
        // Mettre à jour sources: chess.js + canvas
        this.chessJs.load(fen === 'startpos' ? 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1' : fen)
        this.board.setPosition(fen === 'startpos' ? 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1' : fen)
        // Invalider un éventuel coup en attente si le board change côté serveur
        if (this._pending) {
            this._pending = null
            this._hidePendingControls()
            this.printDebug('ℹ️ Coup en attente annulé (état serveur mis à jour)')
        }
    }

    onPollGameUpdated(event) {
        const gs = event?.detail
        if (!gs) return
        // Normaliser turnTeam éventuel
        let t = gs.turnTeam
        if (t === 'TeamA') t = 'A'
        if (t === 'TeamB') t = 'B'
        this.turnTeamValue = t || this.turnTeamValue
        this.statusValue = gs.status || this.statusValue
        this.deadlineTsValue = (gs.turnDeadline ? gs.turnDeadline * 1000 : this.deadlineTsValue)
        this.currentPly = typeof gs.ply === 'number' ? gs.ply : this.currentPly
        // Mettre à jour interactivité selon le tour ET le clic "Prêt"
        const isPlayerTurn = this.isCurrentPlayerTurn()
        this.turnReady = this.isTurnReady()
        let canInteract = this.statusValue === 'live' && isPlayerTurn && this.turnReady

        // Gérer la décision de timeout en attente
        const td = gs.timeoutDecision || {}
        const pending = !!td.pending
        if (pending) {
            // Bloquer l'interaction pendant la décision
            canInteract = false
            const userTeamEl = document.querySelector('[data-user-team]')
            const userTeam = userTeamEl ? userTeamEl.dataset.userTeam : null
            const decisionTeam = (td.decisionTeam === 'TeamA') ? 'A' : (td.decisionTeam === 'TeamB' ? 'B' : td.decisionTeam)
            if (this.hasTimeoutDecisionTarget) {
                // Afficher le panneau seulement pour l'équipe décisionnaire
                this.timeoutDecisionTarget.style.display = (userTeam && decisionTeam && userTeam === decisionTeam) ? 'block' : 'none'
            }
        } else {
            if (this.hasTimeoutDecisionTarget) {
                this.timeoutDecisionTarget.style.display = 'none'
            }
        }

        this.board.setInteractive(!!canInteract)
        this.setupBoardOverlay(isPlayerTurn)
        this.renderState()
    }

    async decideTimeout(event) {
        const decision = event?.currentTarget?.dataset?.decision
        if (!decision) return
        this.printDebug(`🕒 Décision timeout: ${decision}`)
        const ok = await this.apiPost(`/games/${this.gameIdValue}/timeout-decision`, { decision })
        if (!ok) {
            this.printDebug('❌ Décision refusée par le serveur')
            return
        }
        // Forcer un refresh de l'état
        try {
            const gameRes = await fetch(`/games/${this.gameIdValue}/state`, { headers: { 'Accept': 'application/json' } })
            if (gameRes.ok) {
                const gameState = await gameRes.json()
                this.onPollGameUpdated({ detail: gameState })
            }
        } catch (e) {
            // ignore
        }
    }

    async reloadMoves() {
        const res = await fetch(`/games/${this.gameIdValue}/moves`, { headers: { 'Accept': 'application/json' } })
        if (!res.ok) return
        const json = await res.json()
        const list = document.getElementById('moves-list')
        if (!list) return
        list.innerHTML = ''
        const moves = Array.isArray(json.moves) ? json.moves : []
        for (const m of moves) {
            const li = document.createElement('li')
            li.className = 'move-item slide-up'
            const notation = this.formatMoveNotation(m)
            if (notation === '(?)') {
                console.warn('[game-board] Move ignoré (notation inconnue):', m)
                continue
            }
            const teamName = this.normalizeTeamName(m.team)
            li.innerHTML = `
                <span class="move-notation">#${m.ply}: ${notation}</span>
                <span class="move-team team-${teamName}">${teamName.toUpperCase()}</span>
            `
            list.appendChild(li)
        }
        // Auto-scroll vers le dernier coup
        if (list.lastElementChild) {
            list.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'end' })
        }
    }

    // Normalise une équipe retournée potentiellement sous forme d'objet ou de string
    normalizeTeamName(team) {
        if (!team) return ''
        if (typeof team === 'string') return team.toLowerCase()
        // Essayer team.name ou team.teamName
        const name = team.name || team.teamName || ''
        return ('' + name).toLowerCase()
    }

    // Formate une notation de coup robuste, y compris les coups spéciaux (timeout-pass)
    formatMoveNotation(m) {
        const type = m.type || 'normal'
        if (type === 'timeout-pass') {
            return '⏰ timeout'
        }
        const san = m.san
        const uci = m.uci
        if (san && typeof san === 'string') return san
        if (uci && typeof uci === 'string') return uci
        return '(?)'
    }

    async fetchGame() {
        const res = await fetch(`/games/${this.gameIdValue}`, { headers: { 'Accept': 'application/json' } })
        return res.ok ? res.json() : {}
    }

    getPlayerColor() {
        // Détermine la couleur du joueur connecté basé sur son équipe
        const userTeamElement = document.querySelector('[data-user-team]')
        if (userTeamElement) {
            const userTeam = userTeamElement.dataset.userTeam
            return userTeam === 'A' ? 'white' : 'black'
        }
        return 'white' // Par défaut
    }

    async markReady() {
        console.debug('[game-board] markReady()')
        const ok = await this.apiPost(`/games/${this.gameIdValue}/ready`, { ready: true })
        if (ok) {
            this.printDebug('✅ Marqué comme prêt')
            // Recharger la page pour voir les changements
            window.location.reload()
        } else {
            this.printDebug('❌ Erreur lors du marquage comme prêt')
        }
    }

    async markNotReady() {
        console.debug('[game-board] markNotReady()')
        const ok = await this.apiPost(`/games/${this.gameIdValue}/ready`, { ready: false })
        if (ok) {
            this.printDebug('✅ Marqué comme pas prêt')
            // Recharger la page pour voir les changements
            window.location.reload()
        } else {
            this.printDebug('❌ Erreur lors du marquage comme pas prêt')
        }
    }

    isCurrentPlayerTurn() {
        // Vérifier si c'est le tour du joueur actuel
        const userTeamElement = document.querySelector('[data-user-team]')
        if (!userTeamElement) return false
        
        const userTeam = userTeamElement.dataset.userTeam
        const currentTurnTeam = this.turnTeamValue
        
        return userTeam === currentTurnTeam
    }
    
    setupBoardOverlay(isPlayerTurn) {
        const boardEl = this.element.querySelector('#board')
        if (!boardEl) return
        
        // Supprimer l'overlay existant s'il y en a un
        const existingOverlay = boardEl.querySelector('.board-overlay')
        if (existingOverlay) {
            existingOverlay.remove()
        }
        // Déterminer l'état de visibilité du canvas selon le tour et l'état "Prêt"
        const existingCanvas = boardEl.querySelector('canvas')
        if (existingCanvas) {
            const isLive = this.statusValue === 'live'
            const ready = !!this.turnReady
            if (isLive && isPlayerTurn && ready) {
                existingCanvas.style.visibility = 'visible'
            } else {
                existingCanvas.style.visibility = 'hidden'
            }
        }
        
        if (!isPlayerTurn && this.statusValue === 'live') {
            // Créer l'overlay quand ce n'est pas le tour du joueur
            const overlay = document.createElement('div')
            overlay.className = 'board-overlay'
            overlay.innerHTML = `
                <div class="overlay-content">
                    <div class="waiting-message">
                        <i class="material-icons">hourglass_empty</i>
                        <h3>En attente...</h3>
                        <p>C'est au tour de l'adversaire</p>
                    </div>
                </div>
            `
            
            // Styles pour l'overlay
            overlay.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 1);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10;
                border-radius: 8px;
                backdrop-filter: blur(2px);
            `
            
            const content = overlay.querySelector('.overlay-content')
            content.style.cssText = `
                text-align: center;
                color: white;
                padding: 2rem;
            `
            
            const icon = overlay.querySelector('.material-icons')
            icon.style.cssText = `
                font-size: 3rem;
                margin-bottom: 1rem;
                opacity: 0.8;
                animation: spin 2s linear infinite;
            `
            
            // Ajouter l'animation de rotation
            if (!document.querySelector('#board-overlay-styles')) {
                const style = document.createElement('style')
                style.id = 'board-overlay-styles'
                style.textContent = `
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                `
                document.head.appendChild(style)
            }
            
            boardEl.appendChild(overlay)
        } else if (isPlayerTurn && this.statusValue === 'live') {
            // Si c'est mon tour mais que je n'ai pas cliqué "Prêt", masquer le board jusqu'au clic
            if (!this.turnReady) {
                // Garantir aucune interaction tant que l'utilisateur n'a pas cliqué "Prêt"
                this.board.setInteractive(false)
                // Masquer totalement le canvas derrière l'overlay
                const canvas = boardEl.querySelector('canvas')
                if (canvas) {
                    canvas.style.visibility = 'hidden'
                }
                const overlay = document.createElement('div')
                overlay.className = 'board-overlay'
                overlay.innerHTML = `
                    <div class="overlay-content">
                        <div class="waiting-message">
                            <i class="material-icons">bolt</i>
                            <h3>Votre tour</h3>
                            <p>Cliquez sur "Prêt" pour révéler l'échiquier et jouer votre coup</p>
                            <button class="neo-btn neo-btn-primary neo-btn-lg" data-role="ready-to-play">Prêt</button>
                        </div>
                    </div>
                `
                overlay.style.cssText = `
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 1);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10;
                    border-radius: 8px;
                    backdrop-filter: blur(2px);
                `
                const content = overlay.querySelector('.overlay-content')
                content.style.cssText = `text-align:center;color:white;padding:2rem;`
                const icon = overlay.querySelector('.material-icons')
                icon.style.cssText = `font-size:3rem;margin-bottom:1rem;opacity:0.9;`

                overlay.querySelector('[data-role="ready-to-play"]').addEventListener('click', () => {
                    this.setTurnReady()
                    // Autoriser l'interaction et retirer l'overlay
                    this.board.setInteractive(true)
                    // Réafficher le canvas
                    if (canvas) {
                        canvas.style.visibility = 'visible'
                    }
                    overlay.remove()
                })
                boardEl.appendChild(overlay)
            } else {
                // Déjà prêt -> aucune overlay, interactivité déjà gérée
            }
        }
    }

    // --- Gestion de l'état "Prêt" par tour (localStorage) ---
    getTurnKey() {
        const ply = (typeof this.currentPly === 'number' && this.currentPly >= 0) ? this.currentPly : 0
        return `turnReady:${this.gameIdValue}:${ply}`
    }

    isTurnReady() {
        try {
            const key = this.getTurnKey()
            return localStorage.getItem(key) === '1'
        } catch (_) {
            return false
        }
    }

    setTurnReady() {
        try {
            localStorage.setItem(this.getTurnKey(), '1')
            this.turnReady = true
        } catch (_) {
            this.turnReady = true
        }
    }
    
    // Ajoute un bouton flottant sur le board pour activer rapidement le mode rapide ("Prêt à jouer")
    setupReadyButton() {
        const boardEl = this.element.querySelector('#board')
        if (!boardEl) return

        // Supprimer une ancienne version si présente
        const existing = boardEl.querySelector('.ready-floating-button')
        if (existing) return // déjà présent, ne pas dupliquer

        // Rechercher le bouton du timer (s'il est déjà rendu)
        const timerReadyBtn = document.querySelector('[data-controller~="chess-timer"] [data-chess-timer-target="readyButton"]')

        // Créer le bouton flottant
        const btn = document.createElement('button')
        btn.className = 'ready-floating-button'
        btn.type = 'button'
        btn.innerHTML = '<span class="icon">⚡</span><span class="label">Prêt à jouer</span>'

        // Styles
        btn.style.cssText = `
            position: absolute;
            right: 12px;
            bottom: 12px;
            z-index: 20;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 6px 16px rgba(79,70,229,0.35);
            cursor: pointer;
        `

        btn.addEventListener('click', () => {
            // Si le bouton du timer existe, simuler un clic dessus
            const timerBtn = document.querySelector('[data-controller~="chess-timer"] [data-chess-timer-target="readyButton"]')
            if (timerBtn && !timerBtn.disabled) {
                timerBtn.click()
                // feedback visuel local
                btn.disabled = true
                btn.style.opacity = '0.8'
                btn.innerHTML = '<span class="icon">⚡</span><span class="label">Mode rapide activé</span>'
                // Retirer le bouton après un court délai
                setTimeout(() => btn.remove(), 1500)
            } else {
                // Si pas encore dispo, informer et tenter un fallback léger
                this.printDebug('⏳ Bouton prêt (timer) indisponible, réessayez dans une seconde…')
                setTimeout(() => {
                    const retry = document.querySelector('[data-controller~="chess-timer"] [data-chess-timer-target="readyButton"]')
                    if (retry && !retry.disabled) retry.click()
                }, 1000)
            }
        })

        boardEl.appendChild(btn)
    }
    
    

    async apiPost(path, body) {
        const res = await fetch(path, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
            credentials: 'same-origin', // s'assurer que le cookie de session est envoyé
            cache: 'no-store'
        })
        console.debug('[game-board] POST', path, '→', res.status)
        if (res.status === 401) { this.printDebug('⚠️ 401: non connecté'); return false }
        if (res.status === 403) { this.printDebug('⛔ 403: action interdite (pas autorisé / pas votre tour / pas membre)'); return false }
        if (!res.ok) {
            try {
                const data = await res.json()
                this.printDebug('❌ Erreur serveur: ' + (data?.message || res.status))
            } catch (e) {
                this.printDebug('❌ Erreur serveur: ' + res.status)
            }
            return false
        }
        return true
    }

    async claimVictory() {
        console.debug('[game-board] claimVictory()')
        
        const confirmed = confirm('Êtes-vous sûr de vouloir revendiquer la victoire ? Cette action est définitive.')
        if (!confirmed) {
            this.printDebug('❌ Revendication annulée par l\'utilisateur')
            return
        }
        
        const ok = await this.apiPost(`/games/${this.gameIdValue}/claim-victory`, {})
        if (ok) {
            this.printDebug('✅ Victoire revendiquée avec succès')
            // Recharger la page pour voir le résultat
            window.location.reload()
        } else {
            this.printDebug('❌ Erreur lors de la revendication de victoire')
        }
    }
}
