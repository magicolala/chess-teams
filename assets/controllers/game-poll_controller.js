import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static values = { gameId: String, pollInterval: { type: Number, default: 2000 } }
    static targets = ['movesContainer', 'gameStatus', 'currentTurn', 'timer', 'claimSection']

    connect() {
        console.log('🔄 Auto-refresh activé pour la partie', this.gameIdValue)
        
        // Initialiser les notifications
        this.initNotifications()
        
        // État précédent pour détecter les changements
        this.previousState = {
            turnTeam: null,
            currentPlayer: null,
            isMyTurn: false
        }
        
        // Éviter de spammer la console si data-user-team est absent
        this.loggedNoUserTeam = false
        
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
            
            // Détecter les changements et envoyer des notifications si nécessaire
            this.checkForTurnChange(gameState)
            
            // Mettre à jour les différentes parties de l'interface
            this.updateMoves(gameState.moves)
            this.updateGameStatus(gameState.status, gameState.result)
            this.updateCurrentTurn(gameState.turnTeam, gameState.currentPlayer)
            this.updateTimer(gameState.turnDeadline)
            this.updateBoard(gameState.fen)
            this.updateClaimVictorySection(gameState.claimVictory)
            
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
                    <span class="neo-badge neo-badge-sm team-${move.team?.name?.toLowerCase() || ''}">${move.team?.name || ''}</span>
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

    updateClaimVictorySection(claimInfo) {
        if (!this.hasClaimSectionTarget || !claimInfo) return
        
        const canClaim = claimInfo.canClaim
        const claimTeam = claimInfo.claimTeam
        const consecutiveTimeouts = claimInfo.consecutiveTimeouts
        const lastTimeoutTeam = claimInfo.lastTimeoutTeam
        
        // Vérifier si l'utilisateur peut revendiquer
        const userTeamElement = document.querySelector('[data-user-team]')
        
        // Normaliser claimTeam pour être cohérent avec data-user-team
        let normalizedClaimTeam = claimTeam
        if (claimTeam === 'TeamA') normalizedClaimTeam = 'A'
        if (claimTeam === 'TeamB') normalizedClaimTeam = 'B'
        
        const userCanClaim = userTeamElement && canClaim && userTeamElement.dataset.userTeam === normalizedClaimTeam
        
        if (userCanClaim) {
            // Afficher la section de revendication
            this.claimSectionTarget.style.display = 'block'
            
            // Mettre à jour le texte avec les détails
            const alertText = this.claimSectionTarget.querySelector('p')
            if (alertText) {
                const teamName = lastTimeoutTeam === 'A' ? 'les Blancs' : 'les Noirs'
                alertText.textContent = `L'équipe ${teamName} a fait ${consecutiveTimeouts} timeouts consécutifs. Vous pouvez revendiquer la victoire.`
            }
            
            console.log('\ud83d\udea8 Revendication de victoire possible pour l\'utilisateur')
        } else {
            // Masquer la section
            this.claimSectionTarget.style.display = 'none'
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
    
    // === MÉTHODES DE NOTIFICATION ===
    
    async initNotifications() {
        // Ne plus demander automatiquement la permission,
        // c'est maintenant géré par le panneau de contrôle
        console.log('🔔 Système de notifications initialisé')
        
        // Écouter les changements de préférences
        window.addEventListener('chess:notification-settings-changed', (event) => {
            console.log('⚙️ Préférences de notification mises à jour:', event.detail)
        })
    }
    
    getNotificationPreferences() {
        return {
            desktop: localStorage.getItem('chess-notifications-desktop') === 'true',
            sound: localStorage.getItem('chess-notifications-sound') !== 'false', // activé par défaut
            flash: localStorage.getItem('chess-notifications-flash') !== 'false'  // activé par défaut
        }
    }
    
    checkForTurnChange(gameState) {
        const userTeamElement = document.querySelector('[data-user-team]')
        if (!userTeamElement) {
            if (!this.loggedNoUserTeam) {
                console.debug('🤖 Pas d\'attribut data-user-team trouvé')
                this.loggedNoUserTeam = true
            }
            return
        }
        
        const userTeam = userTeamElement.dataset.userTeam // "A" ou "B"
        
        // Normaliser gameState.turnTeam pour être cohérent avec userTeam
        let currentTurn = gameState.turnTeam
        if (currentTurn === 'TeamA') currentTurn = 'A'
        if (currentTurn === 'TeamB') currentTurn = 'B'
        
        const isMyTurnNow = userTeam === currentTurn && gameState.status === 'live'
        
        // Détecter si c'est maintenant mon tour (changement d'état)
        const wasMyTurn = this.previousState.isMyTurn
        const turnChanged = this.previousState.turnTeam !== currentTurn
        
        // Debug pour comprendre les valeurs
        console.debug('🔍 Détection tour:', {
            userTeam,
            currentTurn,
            'gameState.turnTeam': gameState.turnTeam,
            isMyTurnNow,
            wasMyTurn,
            turnChanged,
            'gameState.status': gameState.status
        })
        
        if (turnChanged && isMyTurnNow && !wasMyTurn) {
            console.log('🎯 C\'est maintenant votre tour !', { userTeam, currentTurn })
            this.sendTurnNotification(gameState)
        }
        
        // Mettre à jour l'état précédent
        this.previousState = {
            turnTeam: currentTurn, // Utiliser la valeur normalisée
            currentPlayer: gameState.currentPlayer,
            isMyTurn: isMyTurnNow
        }
    }
    
    sendTurnNotification(gameState) {
        const prefs = this.getNotificationPreferences()
        
        // Notification de bureau (seulement si activée)
        if (prefs.desktop && 'Notification' in window && Notification.permission === 'granted') {
            const notification = new Notification('♟️ Chess Teams - C\'est votre tour !', {
                body: `Il est temps de jouer votre coup dans la partie ${this.gameIdValue.substring(0, 8)}...`,
                icon: '/favicon.ico',
                badge: '/favicon.ico',
                tag: `chess-turn-${this.gameIdValue}`,
                renotify: true,
                requireInteraction: false, // Moins intrusif
                actions: [
                    { action: 'play', title: '🎮 Jouer maintenant' },
                    { action: 'close', title: '❌ Fermer' }
                ]
            })
            
            notification.onclick = () => {
                window.focus()
                notification.close()
            }
            
            // Auto-fermer après 8 secondes
            setTimeout(() => {
                notification.close()
            }, 8000)
        }
        
        // Notification sonore (seulement si activée)
        if (prefs.sound) {
            this.playNotificationSound()
        }
        
        // Flash du titre (seulement si activé)
        if (prefs.flash) {
            this.flashTitle('C\'est votre tour !')
        }
        
        // Notification flash dans l'interface (toujours visible)
        if (window.addFlashMessage) {
            window.addFlashMessage('success', '🎯 C\'est maintenant votre tour de jouer !', {
                duration: 5000,
                persistent: false
            })
        }
    }
    
    playNotificationSound() {
        try {
            // Créer un son de notification simple
            const audioContext = new (window.AudioContext || window.webkitAudioContext)()
            const oscillator = audioContext.createOscillator()
            const gainNode = audioContext.createGain()
            
            oscillator.connect(gainNode)
            gainNode.connect(audioContext.destination)
            
            oscillator.frequency.value = 800
            oscillator.type = 'sine'
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime)
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5)
            
            oscillator.start(audioContext.currentTime)
            oscillator.stop(audioContext.currentTime + 0.5)
        } catch (error) {
            // Ignorer les erreurs audio
            console.debug('Son de notification non disponible:', error.message)
        }
    }
    
    flashTitle(message) {
        const originalTitle = document.title
        let isFlashing = true
        let flashCount = 0
        const maxFlashes = 10
        
        const flashInterval = setInterval(() => {
            if (flashCount >= maxFlashes) {
                document.title = originalTitle
                clearInterval(flashInterval)
                return
            }
            
            document.title = isFlashing ? `🔥 ${message}` : originalTitle
            isFlashing = !isFlashing
            flashCount++
        }, 500)
        
        // Arrêter le clignotement si l'utilisateur revient sur la page
        const stopFlashing = () => {
            document.title = originalTitle
            clearInterval(flashInterval)
            document.removeEventListener('visibilitychange', stopFlashing)
        }
        
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                stopFlashing()
            }
        })
    }
}
