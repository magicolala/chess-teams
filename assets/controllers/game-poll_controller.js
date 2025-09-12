import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static values = { gameId: String, pollInterval: { type: Number, default: 2000 } }
    static targets = ['movesContainer', 'gameStatus', 'currentTurn', 'timer', 'claimSection']

    connect() {
        console.log('🔄 Auto-refresh activé pour la partie', this.gameIdValue)

        this.isDragging = false
        this.element.addEventListener('game-board:drag-start', () => {
            console.debug('[game-poll] Drag detected, polling paused')
            this.isDragging = true
        })
        this.element.addEventListener('game-board:drag-end', () => {
            console.debug('[game-poll] Drag ended, polling resumed')
            this.isDragging = false
        })

        // Initialiser les notifications
        this.initNotifications()

        // Arrêter le polling lorsqu'une connexion Mercure est établie
        this._onMercureConnected = (e) => {
            console.log('📡 Mercure connecté, arrêt du polling pour', this.gameIdValue)
            this.stopPolling()
            // Rafraîchissement immédiat pour aligner l'état
            this.refresh()
        }
        this.element.addEventListener('game-mercure:connected', this._onMercureConnected)

        // État précédent pour détecter les changements
        this.previousState = {
            turnTeam: null,
            currentPlayer: null,
            isMyTurn: false
        }

        // Snapshot léger pour éviter les requêtes lourdes inutiles
        this.previousSnapshot = null // { ply, turnTeam, status }

        // Éviter de spammer la console si data-user-team est absent
        this.loggedNoUserTeam = false

        // Initialiser le dernier ply affiché depuis le DOM existant si possible
        this.lastDisplayedPly = this.getLastDisplayedPly()
        this.startPolling()

        // Gérer les changements de visibilité de la page
        this._onVisibility = this.handleVisibilityChange.bind(this)
        document.addEventListener('visibilitychange', this._onVisibility)
    }

    // --- Notifications & détection de tour ---
    getNotificationPreferences() {
        try {
            const prefs = localStorage.getItem('notificationPrefs')
            return prefs ? JSON.parse(prefs) : { desktop: false, sound: true, flash: true }
        } catch (e) {
            console.warn('Could not load notification preferences:', e)
            return { desktop: false, sound: true, flash: true }
        }
    }

    initNotifications() {
        // Préférences locales (desktop/sound/flash)
        try {
            this.notificationPrefs = (typeof this.getNotificationPreferences === 'function')
                ? this.getNotificationPreferences()
                : { desktop: false, sound: true, flash: true }
        } catch (_) {
            this.notificationPrefs = { desktop: false, sound: true, flash: true }
        }
        // Écouter les changements de préférences provenant du panneau
        this._onNotifPrefsChanged = (e) => {
            const d = e?.detail || {}
            const cur = this.notificationPrefs || {}
            this.notificationPrefs = {
                desktop: typeof d.desktop === 'boolean' ? d.desktop : cur.desktop,
                sound: typeof d.sound === 'boolean' ? d.sound : cur.sound,
                flash: typeof d.flash === 'boolean' ? d.flash : cur.flash,
            }
        }
        window.addEventListener('chess:notification-settings-changed', this._onNotifPrefsChanged)
    }

    disconnect() {
        this.stopPolling()
        if (this._onVisibility) {
            document.removeEventListener('visibilitychange', this._onVisibility)
            this._onVisibility = null
        }
        if (this._onMercureConnected) {
            this.element.removeEventListener('game-mercure:connected', this._onMercureConnected)
            this._onMercureConnected = null
        }
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
        // Ne pas actualiser si un glisser-déposer est en cours
        if (this.isDragging) {
            return
        }

        try {
            // 1) Appel léger pour détecter les changements
            const lightRes = await fetch(`/games/${this.gameIdValue}`, { headers: { 'Accept': 'application/json' } })
            if (!lightRes.ok) {
                console.warn('⚠️ Erreur lors de la récupération des infos de base de la partie')
                return
            }
            const light = await lightRes.json()

            // Mettre à jour l'UI minimale depuis la réponse légère
            this.updateGameStatus(light.status)
            this.updateCurrentTurn(light.turnTeam)
            this.updateTimer(light.turnDeadline)
            this.updateBoard(light.fen)

            const changed = !this.previousSnapshot
                || light.ply !== this.previousSnapshot.ply
                || light.turnTeam !== this.previousSnapshot.turnTeam
                || light.status !== this.previousSnapshot.status

            // Mettre à jour le snapshot pour le prochain tour
            this.previousSnapshot = { ply: light.ply, turnTeam: light.turnTeam, status: light.status }

            if (!changed) {
                // Rien n'a changé, éviter de charger l'état complet
                return
            }

            // 2) Charger de manière incrémentale uniquement les nouveaux coups
            await this.fetchAndAppendNewMoves()

            // 3) Récupérer l'état complet uniquement quand nécessaire (pour currentPlayer, claimVictory, timing avancé)
            const gameRes = await fetch(`/games/${this.gameIdValue}/state`, { headers: { 'Accept': 'application/json' } })
            if (!gameRes.ok) {
                console.warn('⚠️ Erreur lors de la récupération de l\'état complet de la partie')
                return
            }
            const gameState = await gameRes.json()

            // Détecter les changements et envoyer des notifications si nécessaire
            this.checkForTurnChange(gameState)

            // Ne pas recharger la liste des coups depuis /state pour éviter les doublons
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

    // Détermine le dernier ply affiché en lisant la liste des coups
    getLastDisplayedPly() {
        const list = document.getElementById('moves-list')
        if (!list || list.children.length === 0) return 0
        const lastItem = list.lastElementChild
        if (!lastItem) return 0
        // Format texte attendu "#<ply>: ..."
        const text = lastItem.querySelector('.move-notation')?.textContent || ''
        const match = text.match(/#(\d+)\s*:/)
        return match ? parseInt(match[1], 10) : 0
    }

    // Appelle l'API incrémentale pour récupérer et ajouter seulement les nouveaux coups
    async fetchAndAppendNewMoves() {
        const since = typeof this.lastDisplayedPly === 'number' ? this.lastDisplayedPly : 0
        const res = await fetch(`/games/${this.gameIdValue}/moves?since=${since}`, { headers: { 'Accept': 'application/json' } })
        if (!res.ok) return
        const json = await res.json()
        const moves = Array.isArray(json.moves) ? json.moves : []
        if (!moves.length) return

        // Ajouter les nouveaux coups
        const list = document.getElementById('moves-list')
        if (!list) return
        moves.forEach(move => {
            const notation = this.formatMoveNotation(move)
            if (notation === '(?)') {
                console.warn('[game-poll] Move ignoré (notation inconnue):', move)
                return
            }
            const teamName = this.normalizeTeamName(move.team)
            const li = document.createElement('li')
            li.className = 'move-item'
            li.innerHTML = `
                <span class="move-notation">#${move.ply}: ${notation}</span>
                <span class="neo-badge neo-badge-sm team-${teamName}">${teamName.toUpperCase()}</span>
            `
            list.appendChild(li)
        })
        // Mettre à jour le dernier ply
        this.lastDisplayedPly = Math.max(this.getLastDisplayedPly(), this.lastDisplayedPly || 0)
        // Auto-scroll vers le dernier coup
        list.scrollTop = list.scrollHeight
    }

    normalizeTeamName(team) {
        if (!team) return ''
        if (typeof team === 'string') return team.toLowerCase()
        const name = team.name || team.teamName || ''
        return ('' + name).toLowerCase()
    }

    formatMoveNotation(m) {
        const type = m.type || 'normal'
        if (type === 'timeout-pass') return '⏰ timeout'
        const san = m.san
        const uci = m.uci
        if (san && typeof san === 'string') return san
        if (uci && typeof uci === 'string') return uci
        return '(?)'
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

        // Si la partie est terminée, arrêter le polling pour éviter des requêtes inutiles
        if (status === 'finished' || status === 'ended') {
            this.stopPolling()
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

    // Détecte les changements de tour/joueur et déclenche les notifications
    checkForTurnChange(gameState) {
        if (!gameState || typeof gameState !== 'object') return

        const prev = this.previousState || {}
        const turnChanged = gameState.turnTeam && gameState.turnTeam !== prev.turnTeam
        const prevPlayerId = prev.currentPlayer && prev.currentPlayer.id ? prev.currentPlayer.id : null
        const curPlayerId = gameState.currentPlayer && gameState.currentPlayer.id ? gameState.currentPlayer.id : null
        const playerChanged = curPlayerId !== prevPlayerId

        // Mettre à jour l'UI liée au tour/joueur
        if (turnChanged || playerChanged) {
            this.updateCurrentTurn(gameState.turnTeam, gameState.currentPlayer || null)
        }

        // Déterminer si c'est le tour de l'utilisateur
        const isMyTurnNow = this.isUserTurn(gameState.turnTeam)
        const wasMyTurn = !!prev.isMyTurn

        // Déclencher une notification uniquement lors du passage au tour de l'utilisateur
        if (!wasMyTurn && isMyTurnNow) {
            this.forceRefresh()
        }

        // Mettre à jour l'état précédent
        this.previousState = {
            turnTeam: gameState.turnTeam || prev.turnTeam || null,
            currentPlayer: gameState.currentPlayer || null,
            isMyTurn: isMyTurnNow,
        }

        // Si le statut de la partie a changé, synchroniser l'affichage du statut
        if (typeof gameState.status === 'string') {
            // Certains états peuvent inclure un résultat
            this.updateGameStatus(gameState.status, gameState.result || null)
        }
    }

    // Retourne true si l'utilisateur appartient à l'équipe dont c'est le tour
    isUserTurn(turnTeam) {
        const el = document.querySelector('[data-user-team]')
        if (!el) return false
        const userTeam = el.dataset.userTeam
        return !!userTeam && !!turnTeam && userTeam === turnTeam
    }

    getStatusClass(status) {
        switch (status) {
            case 'live': return 'success'
            case 'ended': return 'secondary'
            case 'finished': return 'secondary'
            case 'waiting': return 'warning'
            default: return 'secondary'
        }
    }

    // Actions manuelles
    forceRefresh() {
        console.log('🔄 Actualisation forcée')
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
