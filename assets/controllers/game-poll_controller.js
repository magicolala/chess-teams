import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static values = { gameId: String }

    connect() { this.interval = setInterval(() => this.refresh(), 2000) }
    disconnect() { clearInterval(this.interval) }

    async refresh() {
        const movesRes = await fetch(`/games/${this.gameIdValue}/moves`, { headers: { 'Accept': 'application/json' } })
        if (!movesRes.ok) return
        const json = await movesRes.json()
        const list = document.getElementById('moves-list')
        if (!list) return
        const before = list.children.length
        if (json.moves.length !== before) {
            list.innerHTML = ''
            for (const m of json.moves) {
                const li = document.createElement('li')
                li.textContent = `#${m.ply}: ${m.san ?? m.uci} (${m.team})`
                list.appendChild(li)
            }
        }
    }
}
