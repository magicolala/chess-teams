import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static values = { 
        gameId: String,
        maxDays: Number,    // 14 jours max par coup
        fastTime: Number,   // 60 secondes une fois prêt
        currentDeadline: Number,
        isPlayerTurn: Boolean,
        gameStatus: String
    }
    static targets = ['display', 'progressBar', 'readyButton', 'overlay']

    connect() {
        console.debug('[chess-timer] connect', {
            gameId: this.gameIdValue,
            maxDays: this.maxDaysValue || 14,
            fastTime: this.fastTimeValue || 60,
            isPlayerTurn: this.isPlayerTurnValue
        })

        this.maxDaysValue = this.maxDaysValue || 14
        this.fastTimeValue = this.fastTimeValue || 60
        this.isInFastMode = false
        this.fastModeStartTime = null

        this.setupTimer()
        this.updateDisplay()
        
        // Démarrer le timer
        this.interval = setInterval(() => this.tick(), 250)
    }

    disconnect() {
        if (this.interval) {
            clearInterval(this.interval)
        }
        console.debug('[chess-timer] disconnect')
    }

    setupTimer() {
        if (!this.hasDisplayTarget) return

        // Créer l'interface du timer moderne
        this.displayTarget.innerHTML = `
            <div class="chess-timer">
                <div class="timer-display">
                    <div class="timer-main" data-chess-timer-target="mainTime">00:00:00</div>
                    <div class="timer-mode" data-chess-timer-target="modeIndicator">Temps libre</div>
                </div>
                <div class="timer-progress" data-chess-timer-target="progressBar">
                    <div class="progress-fill" data-chess-timer-target="progressFill"></div>
                </div>
                ${this.isPlayerTurnValue && this.gameStatusValue === 'live' ? `
                    <button class="ready-button" data-action="chess-timer#startFastMode" data-chess-timer-target="readyButton">
                        <span class="button-icon">⚡</span>
                        <span class="button-text">Prêt à jouer</span>
                    </button>
                ` : ''}
            </div>
        `

        this.setupStyles()
        this.bindTargets()
    }

    bindTargets() {
        this.mainTimeTarget = this.displayTarget.querySelector('[data-chess-timer-target="mainTime"]')
        this.modeIndicatorTarget = this.displayTarget.querySelector('[data-chess-timer-target="modeIndicator"]')
        this.progressFillTarget = this.displayTarget.querySelector('[data-chess-timer-target="progressFill"]')
        this.readyButtonTarget = this.displayTarget.querySelector('[data-chess-timer-target="readyButton"]')
    }

    setupStyles() {
        // Ajouter les styles CSS modernes pour le timer
        if (document.querySelector('#chess-timer-styles')) return

        const style = document.createElement('style')
        style.id = 'chess-timer-styles'
        style.textContent = `
            .chess-timer {
                background: linear-gradient(135deg, #1e1e22, #2a2a2e);
                border-radius: 12px;
                padding: 1.5rem;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.1);
                text-align: center;
                min-width: 280px;
            }

            .timer-display {
                margin-bottom: 1rem;
            }

            .timer-main {
                font-family: 'JetBrains Mono', monospace;
                font-size: 2.5rem;
                font-weight: 600;
                color: #e6e9ee;
                text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
                margin-bottom: 0.5rem;
                letter-spacing: -0.02em;
            }

            .timer-main.urgent {
                color: #ef4444;
                animation: pulse-red 1s infinite;
            }

            .timer-main.fast-mode {
                color: #10b981;
                animation: pulse-green 1s infinite;
            }

            @keyframes pulse-red {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.7; }
            }

            @keyframes pulse-green {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.8; }
            }

            .timer-mode {
                font-size: 0.875rem;
                color: #c2c8d2;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                font-weight: 500;
            }

            .timer-mode.fast {
                color: #10b981;
            }

            .timer-progress {
                background: rgba(255, 255, 255, 0.1);
                height: 8px;
                border-radius: 4px;
                overflow: hidden;
                margin-bottom: 1.5rem;
                position: relative;
            }

            .progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #10b981, #059669);
                border-radius: 4px;
                transition: width 0.25s ease-out;
                position: relative;
            }

            .progress-fill.urgent {
                background: linear-gradient(90deg, #ef4444, #dc2626);
            }

            .progress-fill.fast-mode {
                background: linear-gradient(90deg, #10b981, #047857);
                animation: shimmer 1.5s infinite;
            }

            @keyframes shimmer {
                0% { background-position: -200% 0; }
                100% { background-position: 200% 0; }
            }

            .ready-button {
                background: linear-gradient(135deg, #4f46e5, #7c3aed);
                border: none;
                border-radius: 8px;
                color: white;
                padding: 1rem 2rem;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.75rem;
                width: 100%;
                transition: all 0.2s ease;
                box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            }

            .ready-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
                background: linear-gradient(135deg, #5b52e6, #8b5cf6);
            }

            .ready-button:active {
                transform: translateY(0);
            }

            .ready-button.activated {
                background: linear-gradient(135deg, #10b981, #059669);
                box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            }

            .ready-button.activated:hover {
                box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
            }

            .button-icon {
                font-size: 1.25rem;
                animation: bounce 2s infinite;
            }

            @keyframes bounce {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-4px); }
            }

            .button-text {
                font-family: 'Inter', sans-serif;
            }
        `
        document.head.appendChild(style)
    }

    tick() {
        this.updateDisplay()
        this.updateProgress()
        
        if (this.isInFastMode) {
            const elapsed = (Date.now() - this.fastModeStartTime) / 1000
            const remaining = this.fastTimeValue - elapsed
            
            if (remaining <= 0) {
                this.handleTimeout()
            }
        }
    }

    updateDisplay() {
        if (!this.mainTimeTarget) return

        let timeRemaining, displayText, isUrgent = false

        if (this.isInFastMode && this.fastModeStartTime) {
            // Mode rapide : 1 minute
            const elapsed = (Date.now() - this.fastModeStartTime) / 1000
            timeRemaining = Math.max(0, this.fastTimeValue - elapsed)
            displayText = this.formatTime(timeRemaining)
            isUrgent = timeRemaining <= 10
            
            this.modeIndicatorTarget.textContent = 'Mode rapide'
            this.modeIndicatorTarget.classList.add('fast')
            this.mainTimeTarget.classList.add('fast-mode')
        } else if (this.currentDeadlineValue) {
            // Mode normal : jusqu'à 14 jours
            timeRemaining = Math.max(0, (this.currentDeadlineValue - Date.now()) / 1000)
            displayText = this.formatLongTime(timeRemaining)
            isUrgent = timeRemaining <= 3600 // Urgent dans la dernière heure
            
            this.modeIndicatorTarget.textContent = 'Temps libre'
            this.modeIndicatorTarget.classList.remove('fast')
            this.mainTimeTarget.classList.remove('fast-mode')
        } else {
            displayText = '--:--:--'
            this.modeIndicatorTarget.textContent = 'En attente'
        }

        this.mainTimeTarget.textContent = displayText
        
        if (isUrgent) {
            this.mainTimeTarget.classList.add('urgent')
        } else {
            this.mainTimeTarget.classList.remove('urgent')
        }
    }

    updateProgress() {
        if (!this.progressFillTarget) return

        let percentage = 0

        if (this.isInFastMode && this.fastModeStartTime) {
            const elapsed = (Date.now() - this.fastModeStartTime) / 1000
            percentage = Math.max(0, Math.min(100, (elapsed / this.fastTimeValue) * 100))
            
            this.progressFillTarget.classList.add('fast-mode')
        } else if (this.currentDeadlineValue) {
            const maxTime = this.maxDaysValue * 24 * 3600 // En secondes
            const remaining = (this.currentDeadlineValue - Date.now()) / 1000
            percentage = Math.max(0, Math.min(100, ((maxTime - remaining) / maxTime) * 100))
            
            this.progressFillTarget.classList.remove('fast-mode')
        }

        if (percentage > 80) {
            this.progressFillTarget.classList.add('urgent')
        } else {
            this.progressFillTarget.classList.remove('urgent')
        }

        this.progressFillTarget.style.width = `${percentage}%`
    }

    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60)
        const secs = Math.floor(seconds % 60)
        return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
    }

    formatLongTime(seconds) {
        const days = Math.floor(seconds / (24 * 3600))
        const hours = Math.floor((seconds % (24 * 3600)) / 3600)
        const minutes = Math.floor((seconds % 3600) / 60)
        const secs = Math.floor(seconds % 60)

        if (days > 0) {
            return `${days}j ${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
        } else {
            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
        }
    }

    startFastMode() {
        if (this.isInFastMode) return

        console.debug('[chess-timer] Starting fast mode')
        
        this.isInFastMode = true
        this.fastModeStartTime = Date.now()
        
        if (this.readyButtonTarget) {
            this.readyButtonTarget.classList.add('activated')
            this.readyButtonTarget.innerHTML = `
                <span class="button-icon">⚡</span>
                <span class="button-text">Mode rapide activé</span>
            `
            this.readyButtonTarget.disabled = true
        }

        // Notifier le serveur du début du mode rapide
        this.notifyFastModeStart()
    }

    async notifyFastModeStart() {
        try {
            const response = await fetch(`/games/${this.gameIdValue}/start-fast-mode`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    fastTime: this.fastTimeValue
                })
            })

            if (!response.ok) {
                console.warn('[chess-timer] Failed to notify fast mode start')
            }
        } catch (error) {
            console.error('[chess-timer] Error notifying fast mode start:', error)
        }
    }

    handleTimeout() {
        console.debug('[chess-timer] Timeout reached')
        
        // Envoyer timeout au serveur
        fetch(`/games/${this.gameIdValue}/timeout`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        }).then(response => {
            if (response.ok) {
                // Recharger la page pour voir les changements
                window.location.reload()
            }
        }).catch(error => {
            console.error('[chess-timer] Error handling timeout:', error)
        })
    }

    // Méthodes pour être appelées depuis l'extérieur
    updateDeadline(newDeadline) {
        this.currentDeadlineValue = newDeadline
    }

    setPlayerTurn(isPlayerTurn) {
        this.isPlayerTurnValue = isPlayerTurn
        
        if (isPlayerTurn && !this.hasReadyButtonTarget && this.gameStatusValue === 'live') {
            this.setupTimer() // Re-setup pour ajouter le bouton
        }
    }
}
