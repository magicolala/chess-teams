import { Controller } from '@hotwired/stimulus'
import { Chessground } from 'chessground'
import { Chess } from 'chess.js'

export default class extends Controller {
    static values = { fen: String, gameId: String, turnTeam: String, deadlineTs: Number, status: String, result: String }
    static targets = ['timer', 'turnTeam', 'status', 'result']

    connect() {
        this.cg = Chessground(document.getElementById('board'), {
            fen: this.fenValue === 'startpos' ? undefined : this.fenValue,
            draggable: { enabled: true },
            events: { move: (from, to) => this.onDragMove(from, to) }
        })
        this.timerInterval = setInterval(() => this.tickTimer(), 250)
        this.renderState()
    }
    disconnect() { clearInterval(this.timerInterval); this.cg?.destroy?.() }

    renderState() {
        if (this.hasTurnTeamTarget) this.turnTeamTarget.textContent = this.turnTeamValue
        if (this.hasStatusTarget) this.statusTarget.textContent = this.statusValue
        if (this.hasResultTarget) this.resultTarget.textContent = this.resultValue || ''
    }
    tickTimer() {
        if (!this.deadlineTsValue || this.statusValue === 'finished') {
            if (this.hasTimerTarget) this.timerTarget.textContent = '-'
            return
        }
        const remain = Math.max(0, Math.floor((this.deadlineTsValue - Date.now()) / 1000))
        if (this.hasTimerTarget) this.timerTarget.textContent = remain + 's'
    }

    async onDragMove(from, to) {
        const uci = from + to // TODO: gérer la promotion (ajouter 'q' si besoin)
        await this.sendMove(uci)
    }
    async offerMove(e) { await this.sendMove(e.currentTarget.dataset.uci) }
    async tick() {
        await this.apiPost(`/games/${this.gameIdValue}/tick`, {})
        const g = await this.fetchGame()
        this.updateGameState(g)
    }

    async sendMove(uci) {
        const ok = await this.apiPost(`/games/${this.gameIdValue}/move`, { uci })
        if (!ok) {
            // Re-sync board with server state if move was illegal
            const g = await this.fetchGame()
            this.cg.set({ fen: g.fen === 'startpos' ? undefined : g.fen })
            return
        }
        const g = await this.fetchGame()
        this.updateGameState(g)
    }

    updateGameState(gameData) {
        if (!gameData || !gameData.id) return

        this.fenValue = gameData.fen
        this.turnTeamValue = gameData.turnTeam
        this.deadlineTsValue = gameData.turnDeadline || 0
        this.statusValue = gameData.status
        this.resultValue = gameData.result || ''

        this.cg.set({ fen: gameData.fen === 'startpos' ? undefined : gameData.fen })
        this.renderState()
        this.reloadMoves()
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
            li.textContent = `#${m.ply}: ${m.san ?? m.uci} (${m.team.name})`
            list.appendChild(li)
        }
    }

    async fetchGame() {
        const res = await fetch(`/games/${this.gameIdValue}`, { headers: { 'Accept': 'application/json' } })
        return res.ok ? res.json() : {}
    }

    async apiPost(path, body) {
        const res = await fetch(path, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
        if (res.status === 401) { alert('Connecte-toi'); return false }
        if (res.status === 409) { /* finished/locked */ return false }
        if (res.status === 422) {
            alert('Coup illégal')
            const g = await this.fetchGame()
            this.cg.set({ fen: g.fen === 'startpos' ? undefined : g.fen })
            return false
        }
        return res.ok
    }
}
