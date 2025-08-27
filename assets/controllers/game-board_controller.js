import { Controller } from '@hotwired/stimulus'
import { Chessground } from 'chessground'
import { Chess as ChessJs } from 'chess.js'

export default class extends Controller {
    static values = { fen: String, gameId: String, turnTeam: String, deadlineTs: Number, status: String }
    static targets = ['timer', 'turnTeam', 'status', 'result']

    connect() {
        console.debug('[game-board] connect()', {
            fen: this.fenValue, gameId: this.gameIdValue, turnTeam: this.turnTeamValue,
            deadlineTs: this.deadlineTsValue, status: this.statusValue
        })

        const boardEl = this.element.querySelector('#board')
        if (!boardEl) {
            console.error('[game-board] #board introuvable dans l’élément du contrôleur', this.element)
            this.printDebug('❌ #board introuvable. Vérifie l’id="board" dans le HTML.')
            return
        }

        this.chessJs = new ChessJs(this.fenValue === 'startpos' ? undefined : this.fenValue)

        try {
            this.cg = Chessground(boardEl, {
                fen: this.fenValue === 'startpos' ? undefined : this.fenValue,
                animation: { duration: 150 },
                draggable: { enabled: true },
                highlight: { 
                    lastMove: true, 
                    check: true 
                },
                movable: {
                    free: false,
                    color: this.getPlayerColor(),
                    dests: this.calcLegalMoves()
                },
                drawable: {
                    enabled: true,
                    visible: true
                },
                events: { 
                    move: (from, to) => this.onDragMove(from, to),
                    select: (key) => this.onSquareSelect(key)
                }
            })
            console.debug('[game-board] Chessground prêt', this.cg)
            this.printDebug('✅ Chessground initialisé')
        } catch (e) {
            console.error('[game-board] échec init Chessground', e)
            this.printDebug('❌ Erreur init Chessground: ' + e?.message)
            return
        }

        // Sanity: dimensions
        const rect = boardEl.getBoundingClientRect()
        console.debug('[game-board] board rect', rect)
        if (rect.width === 0 || rect.height === 0) {
            this.printDebug('⚠️ Board a 0x0 px. Ajoute une taille CSS (.cg-wrap {width/height}).')
            boardEl.style.outline = '2px solid red'
        }

        // Timer
        this.timerInterval = setInterval(() => this.tickTimer(), 250)
        this.renderState()
    }

    disconnect() {
        clearInterval(this.timerInterval)
        this.cg?.destroy?.()
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

    async onDragMove(from, to) {
        const uci = from + to // TODO: promo
        console.debug('[game-board] drag move', uci)
        await this.sendMove(uci)
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
        
        this.cg.set({ 
            fen: g.fen === 'startpos' ? undefined : g.fen,
            movable: {
                color: this.getPlayerColor(),
                dests: this.calcLegalMoves()
            }
        })
        
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

    calcLegalMoves() {
        const dests = new Map()
        for (const square of ['a1', 'b1', 'c1', 'd1', 'e1', 'f1', 'g1', 'h1',
                              'a2', 'b2', 'c2', 'd2', 'e2', 'f2', 'g2', 'h2',
                              'a3', 'b3', 'c3', 'd3', 'e3', 'f3', 'g3', 'h3',
                              'a4', 'b4', 'c4', 'd4', 'e4', 'f4', 'g4', 'h4',
                              'a5', 'b5', 'c5', 'd5', 'e5', 'f5', 'g5', 'h5',
                              'a6', 'b6', 'c6', 'd6', 'e6', 'f6', 'g6', 'h6',
                              'a7', 'b7', 'c7', 'd7', 'e7', 'f7', 'g7', 'h7',
                              'a8', 'b8', 'c8', 'd8', 'e8', 'f8', 'g8', 'h8']) {
            try {
                const moves = this.chessJs.moves({ square, verbose: true })
                if (moves.length > 0) {
                    dests.set(square, moves.map(m => m.to))
                }
            } catch (e) {
                continue
            }
        }
        return dests
    }

    onSquareSelect(key) {
        console.debug('[game-board] square selected:', key)
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
