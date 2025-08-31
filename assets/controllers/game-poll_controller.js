import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static values = { gameId: String, pollInterval: { type: Number, default: 2000 } }
    static targets = ['movesContainer', 'gameStatus', 'currentTurn', 'timer']

    connect() {
        console.log('🔄 Auto-refresh activé pour la partie', this.gameIdValue)
        this.startPolling()
        
        // Gérer les changements de visibilité de la page
        document.addEventListener('visibilitychange', this.handleVisibilityChange.bind(this))
    }

    disconnect() {
        this.stopPolling()
        document.removeEventListener('visibilitychange', this.handleVisibilityChange.bind(this))
    }

    startPolling() {
        if (this.interval) return
        this.interval = setInterval(() => this.refresh(), this.pollIntervalValue)
        console.log(`🕒 Polling démarré toutes les ${this.pollIntervalValue}ms`)
    }

    stopPolling() {
        if (this.interval) {
            clearInterval(this.interval)
            this.interval = null
            console.log('⏹️ Polling arrêté')
        }
    }

    handleVisibilityChange() {
        if (document.hidden) {
            this.stopPolling()
        } else {
            this.startPolling()
            // Actualisation immédiate quand on revient sur la page
            this.refresh()
        }
    }

    async refresh() {
        try {
            // Récupérer l'état complet de la partie
            const gameRes = await fetch(`/games/${this.gameIdValue}/state`, {
                headers: { 'Accept': 'application/json' }
            })
            
            if (!gameRes.ok) {
                console.warn('⚠️ Erreur lors de la récupération de l\'état de la partie')
                return
            }
            
            const gameState = await gameRes.json()
            
            // Mettre à jour les différentes parties de l'interface
            this.updateMoves(gameState.moves)
            this.updateGameStatus(gameState.status, gameState.result)
            this.updateCurrentTurn(gameState.turnTeam, gameState.currentPlayer)
            this.updateTimer(gameState.turnDeadline)
            this.updateBoard(gameState.fen)
            
            // Émettre un événement personnalisé pour que d'autres contrôleurs puissent réagir
            this.dispatch('gameUpdated', { detail: gameState })
            
        } catch (error) {
            console.error('❌ Erreur lors de l\'actualisation:', error)
        }
    }

    updateMoves(moves) {
        const list = document.getElementById('moves-list')
        if (!list || !moves) return
        
        const currentCount = list.children.length
        if (moves.length !== currentCount) {
            list.innerHTML = ''
            moves.forEach(move => {
                const li = document.createElement('li')
                li.className = 'move-item'
                li.innerHTML = `
                    <span class="move-notation">#${move.ply}: ${move.san ?? move.uci}</span>
                    <span class="neo-badge neo-badge-sm team-${move.team.name.toLowerCase()}">${move.team.name}</span>
                `
                list.appendChild(li)
            })
            
            // Auto-scroll vers le dernier coup
            list.scrollTop = list.scrollHeight
        }
    }

    updateGameStatus(status, result = null) {
        if (this.hasGameStatusTarget) {
            this.gameStatusTarget.textContent = status
            this.gameStatusTarget.className = `neo-badge neo-badge-${this.getStatusClass(status)}`
        }
        
        // Afficher le résultat si la partie est terminée
        const resultElement = document.querySelector('[data-game-board-target="result"]')
        if (resultElement && result) {
            resultElement.textContent = result
            resultElement.className = 'neo-badge neo-badge-success neo-ml-sm'
        }
    }

    updateCurrentTurn(turnTeam, currentPlayer = null) {
        if (this.hasCurrentTurnTarget) {
            this.currentTurnTarget.textContent = turnTeam
            this.currentTurnTarget.className = `neo-badge neo-badge-${turnTeam === 'A' ? 'primary' : 'warning'}`
        }
        
        // Mettre à jour l'affichage du joueur actuel
        const playerElements = document.querySelectorAll('.player-item')
        playerElements.forEach(el => {
            el.classList.remove('current-player')
        })
        
        if (currentPlayer) {
            const currentPlayerEl = document.querySelector(`[data-player-id="${currentPlayer.id}"]`)
            if (currentPlayerEl) {
                currentPlayerEl.classList.add('current-player')
            }
        }
    }

    updateTimer(deadline) {
        if (!this.hasTimerTarget || !deadline) return
        
        const now = Math.floor(Date.now() / 1000)
        const remaining = deadline - now
        
        if (remaining <= 0) {
            this.timerTarget.textContent = '⏰ Temps écoulé'
            this.timerTarget.className = 'neo-text-sm neo-font-mono neo-text-error'
        } else {
            const minutes = Math.floor(remaining / 60)
            const seconds = remaining % 60
            this.timerTarget.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`
            
            // Changer la couleur selon le temps restant
            if (remaining < 30) {
                this.timerTarget.className = 'neo-text-sm neo-font-mono neo-text-error'
            } else if (remaining < 60) {
                this.timerTarget.className = 'neo-text-sm neo-font-mono neo-text-warning'
            } else {
                this.timerTarget.className = 'neo-text-sm neo-font-mono neo-text-success'
            }
        }
    }

    updateBoard(fen) {
        // Dispatche un événement pour que le contrôleur de l'échiquier se mette à jour
        if (fen) {
            this.dispatch('fenUpdated', { detail: { fen } })
        }
    }

    getStatusClass(status) {
        switch (status) {
            case 'live': return 'success'
            case 'ended': return 'secondary'
            case 'waiting': return 'warning'
            default: return 'secondary'
        }
    }

    // Actions manuelles
    forceRefresh() {
        console.log('🔄 Actualisation forcée')
        this.refresh()
    }

    changeInterval(event) {
        const newInterval = parseInt(event.target.value) * 1000
        this.pollIntervalValue = newInterval
        this.stopPolling()
        this.startPolling()
        console.log(`⏰ Intervalle de polling changé : ${newInterval}ms`)
    }
}
