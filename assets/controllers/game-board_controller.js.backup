import { Controller } from '@hotwired/stimulus'
import $ from 'jquery'
import Chessboard from 'chessboardjs'
import { Chess as ChessJs } from 'chess.js'

// Rendre jQuery global pour chessboard.js
window.$ = window.jQuery = $

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
            this.board = Chessboard(boardEl, {
                position: this.fenValue === 'startpos' ? 'start' : this.fenValue,
                draggable: true,
                pieceTheme: 'https://chessboardjs.com/img/chesspieces/wikipedia/{piece}.png',
                onDragStart: (source, piece, position, orientation) => {
                    return this.onDragStart(source, piece, position, orientation)
                },
                onDrop: (source, target) => {
                    return this.onDrop(source, target)
                },
                onSnapEnd: () => {
                    this.board.position(this.chessJs.fen())
                }
            })
            console.debug('[game-board] Chessboard.js prêt', this.board)
            this.printDebug('✅ Chessboard.js initialisé')
        } catch (e) {
            console.error('[game-board] échec init Chessboard.js', e)
            this.printDebug('❌ Erreur init Chessboard.js: ' + e?.message)
            return
        }

        // Sanity: dimensions
        const rect = boardEl.getBoundingClientRect()
        console.debug('[game-board] board rect', rect)
        if (rect.width === 0 || rect.height === 0) {
            this.printDebug('⚠️ Board a 0x0 px. Ajoute une taille CSS au conteneur.')
            boardEl.style.outline = '2px solid red'
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

    onDragStart(source, piece, position, orientation) {
        // Ne permettre de déplacer que si la partie est en cours
        if (this.statusValue === 'finished') return false
        
        // Pour l'instant, permettre de déplacer toutes les pièces (simplification)
        // TODO: implémenter la vérification des équipes plus tard
        return true
    }

    onDrop(source, target) {
        // Sauvegarder la position actuelle pour pouvoir revenir en arrière si nécessaire
        const originalPos = this.chessJs.fen()
        
        // Voir si le coup est légal
        const move = this.chessJs.move({
            from: source,
            to: target,
            promotion: 'q' // Toujours promouvoir en dame pour simplifier
        })

        // Coup illégal - revenir à la position d'origine
        if (move === null) {
            return 'snapback'
        }

        // Coup légal - l'envoyer au serveur
        this.sendMove(move.from + move.to + (move.promotion || ''))
        
        // Laisser la pièce à sa nouvelle position temporairement
        // Elle sera mise à jour par le serveur
        return true
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
        
        this.board.position(g.fen === 'startpos' ? 'start' : g.fen)
        
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
