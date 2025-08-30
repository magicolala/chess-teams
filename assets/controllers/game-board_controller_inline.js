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
    whitePiece: "#ffffff",
    blackPiece: "#000000",
    pieceShadow: "rgba(0,0,0,0.5)",
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
  const parts = fen.split(' ');
  const position = parts[0];
  const turn = parts[1];
  const castling = parts[2];
  const ep = parts[3] === '-' ? null : parts[3];
  const halfmove = parseInt(parts[4]) || 0;
  const fullmove = parseInt(parts[5]) || 1;

  const board = Array(8).fill(null).map(() => Array(8).fill(null));
  let rank = 7, file = 0;

  for (const char of position) {
    if (char === '/') {
      rank--;
      file = 0;
    } else if (char >= '1' && char <= '8') {
      file += parseInt(char);
    } else {
      board[rank][file] = char;
      file++;
    }
  }

  return { board, turn, castling, ep, halfmove, fullmove };
}

const PIECE_UNICODE = {
  'K': '♔', 'Q': '♕', 'R': '♖', 'B': '♗', 'N': '♘', 'P': '♙',
  'k': '♚', 'q': '♛', 'r': '♜', 'b': '♝', 'n': '♞', 'p': '♟'
};

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
    
    ctx.font = `${squareSize * 0.75}px Arial`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    
    for (let rank = 0; rank < 8; rank++) {
      for (let file = 0; file < 8; file++) {
        const piece = this.state.board[rank][file];
        if (!piece) continue;
        
        const square = sq(file, rank);
        if (this.dragging && this.dragging.from === square) continue;
        
        const pos = this.squareToXY(square);
        const centerX = pos.x + squareSize / 2;
        const centerY = pos.y + squareSize / 2;
        
        ctx.fillStyle = this.theme.pieceShadow;
        ctx.fillText(PIECE_UNICODE[piece], centerX + 2, centerY + 2);
        
        ctx.fillStyle = isWhitePiece(piece) ? this.theme.whitePiece : this.theme.blackPiece;
        ctx.fillText(PIECE_UNICODE[piece], centerX, centerY);
      }
    }
    
    if (this.dragging) {
      const centerX = this.dragging.x;
      const centerY = this.dragging.y;
      const piece = this.dragging.piece;
      
      ctx.save();
      ctx.font = `${squareSize * 0.85}px Arial`;
      
      ctx.fillStyle = this.theme.pieceShadow;
      ctx.fillText(PIECE_UNICODE[piece], centerX + 3, centerY + 3);
      
      ctx.fillStyle = isWhitePiece(piece) ? this.theme.whitePiece : this.theme.blackPiece;
      ctx.fillText(PIECE_UNICODE[piece], centerX, centerY);
      
      ctx.restore();
    }
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
    if (!this.interactive) return;
    
    this.canvas.addEventListener('pointerdown', (e) => this.onPointerDown(e));
    this.canvas.addEventListener('pointermove', (e) => this.onPointerMove(e));
    this.canvas.addEventListener('pointerup', (e) => this.onPointerUp(e));
    this.canvas.addEventListener('contextmenu', (e) => e.preventDefault());
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
    
    const toPos = sqToFR(to);
    this.state.board[toPos.r][toPos.f] = piece;
    this.state.board[fromPos.r][fromPos.f] = null;
    this.lastMove = { from, to };
    
    this.gamePosition = this.generateFEN();
    this.emit('move', { from, to, fen: this.gamePosition });
    this.render();
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

  destroy() {
    if (this.resizeObserver) {
      this.resizeObserver.disconnect();
    }
    this.element.innerHTML = '';
  }
}

export default class extends Controller {
    static values = { fen: String, gameId: String, turnTeam: String, deadlineTs: Number, status: String }
    static targets = ['timer', 'turnTeam', 'status', 'result']

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
            this.board = new SimpleNeoChessBoard(boardEl, {
                fen: this.fenValue === 'startpos' ? undefined : this.fenValue,
                theme: 'midnight',
                interactive: true,
                showCoordinates: true,
                orientation: 'white'
            })

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

        // Timer
        this.timerInterval = setInterval(() => this.tickTimer(), 250)
        this.renderState()
    }

    disconnect() {
        clearInterval(this.timerInterval)
        this.board?.destroy?.()
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
    onNeoMove(from, to) {
        // Ne permettre de bouger que si la partie est en cours
        if (this.statusValue === 'finished') {
            this.printDebug('❌ Partie terminée, coup rejeté')
            return
        }

        // Vérifier si c'est un coup légal avec chess.js aussi
        const originalPos = this.chessJs.fen()
        
        // Voir si le coup est légal
        const move = this.chessJs.move({
            from: from,
            to: to,
            promotion: 'q' // Toujours promouvoir en dame pour simplifier
        })

        // Coup illégal - revenir à la position d'origine
        if (move === null) {
            console.warn('[game-board] Coup rejeté par chess.js:', from, to)
            this.printDebug(`❌ Coup rejeté par chess.js: ${from}-${to}`)
            // Remettre la position sur le board Neo
            this.board.setPosition(originalPos, true)
            return
        }

        // Coup légal - l'envoyer au serveur
        this.sendMove(move.from + move.to + (move.promotion || ''))
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
        const ok = await this.apiPost(`/games/${this.gameIdValue}/move`, { uci })
        if (!ok) { this.printDebug('❌ Move refusé'); return }
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
    }

    async reloadMoves() {
        const res = await fetch(`/games/${this.gameIdValue}/moves`, { headers: { 'Accept': 'application/json' } })
        if (!res.ok) return
        const json = await res.json()
        const list = document.getElementById('moves-list')
        if (!list) return
        list.innerHTML = ''
        for (const m of json.moves) {
            const li = document.createElement('li')
            li.className = 'move-item slide-up'
            li.innerHTML = `
                <span class="move-notation">#${m.ply}: ${m.san ?? m.uci}</span>
                <span class="move-team team-${m.team.toLowerCase()}">${m.team}</span>
            `
            list.appendChild(li)
        }
        // Auto-scroll vers le dernier coup
        if (list.lastElementChild) {
            list.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'end' })
        }
    }

    async fetchGame() {
        const res = await fetch(`/games/${this.gameIdValue}`, { headers: { 'Accept': 'application/json' } })
        return res.ok ? res.json() : {}
    }

    getPlayerColor() {
        return 'white'
    }

    async apiPost(path, body) {
        const res = await fetch(path, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
        console.debug('[game-board] POST', path, '→', res.status)
        if (res.status === 401) { this.printDebug('⚠️ 401: non connecté'); return false }
        if (res.status === 409) { this.printDebug('⚠️ 409: conflit (fini/verrouillé)'); return false }
        if (res.status === 422) { this.printDebug('⚠️ 422: coup illégal'); return false }
        return res.ok
    }
}
