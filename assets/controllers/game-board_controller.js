import { Controller } from '@hotwired/stimulus'
import { NeoChessBoard } from '@magicolala/neo-chess-board'
import { Chess as ChessJs } from 'chess.js'

export default class extends Controller {
    static values = {
        fen: String,
        gameId: String,
        turnTeam: String,
        deadlineTs: Number,
        status: String,
        mode: String,
        membershipId: String,
        handBrainCurrentRole: String,
        handBrainPieceHint: String,
        handBrainBrainMemberId: String,
        handBrainHandMemberId: String,
    }
    static targets = [
        'timer',
        'turnTeam',
        'status',
        'result',
        'timeoutDecision',
        'handBrainPanel',
        'handBrainPhase',
        'handBrainHint',
        'handBrainCountdown',
        'handBrainRoleBrain',
        'handBrainRoleHand',
        'handBrainPieceButton',
    ]

    connect() {
        console.debug('[game-board] connect() with Neo Chess Board (npm package)', {
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

        this.handBrainState = {
            currentRole: this.handBrainCurrentRoleValue || null,
            pieceHint: this.handBrainPieceHintValue || null,
            brainMemberId: this.handBrainBrainMemberIdValue || null,
            handMemberId: this.handBrainHandMemberIdValue || null,
        }
        this.handBrainHintLoading = false

        try {
            // Configuration du Neo Chess Board
            // Déterminer l'orientation de l'échiquier selon la couleur du joueur
            const playerColor = this.getPlayerColor()
            const isPlayerTurn = this.isCurrentPlayerTurn()
            
            // Au démarrage, si c'est mon tour et la partie est live, ne pas autoriser l'interaction tant que "Prêt" n'est pas cliqué
            const initialTurnReady = false
            let initialCanInteract = this.statusValue === 'live' && isPlayerTurn && initialTurnReady
            if (this.isHandBrainMode()) {
                initialCanInteract = this.computeHandBrainInteractivity(initialCanInteract)
            }
            this.board = new NeoChessBoard(boardEl, {
                fen: this.fenValue === 'startpos' ? undefined : this.fenValue,
                theme: 'midnight',
                interactive: initialCanInteract, // Interactif seulement si c'est le tour du joueur ET prêt
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
            this.setupBoardOverlay(isPlayerTurn, initialCanInteract)

            // Écouteurs d'événements Neo Chess Board
            this.board.on('move', ({ from, to, fen }) => {
                console.debug('[game-board] Neo Chess Board move event', { from, to, fen })
                this.onNeoMove(from, to)
            })

            this.board.on('illegal', ({ from, to, reason }) => {
                console.debug('[game-board] Neo Chess Board illegal move', { from, to, reason })
                this.printDebug(`❌ Coup illégal: ${from}-${to} (${reason})`)
            })

            // Dispatch drag events for other controllers to listen to
            this.board.on('drag-start', () => this.dispatch('drag-start'))
            this.board.on('drag-end', () => this.dispatch('drag-end'))

            console.debug('[game-board] Neo Chess Board prêt', this.board)
            this.printDebug('✅ Neo Chess Board initialisé (package)')
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
        this.renderHandBrainPanel()

        // Gate "Prêt" par tour: on stocke l'état 'ready' par (gameId, ply)
        this.currentPly = null
        this.turnReady = false

        // Charger la liste des coups avec fenAfter pour permettre la navigation PGN
        // Cela remplace la liste SSR par une liste enrichie (data-fen-after)
        this.reloadMoves().catch(() => {})

        // Écouteur délégué pour gérer les clics sur les coups (navigation PGN)
        this._onMoveItemClick = this.onMoveItemClick.bind(this)
        const movesList = document.getElementById('moves-list')
        if (movesList) {
            movesList.addEventListener('click', this._onMoveItemClick)
        }
    }

    disconnect() {
        clearInterval(this.timerInterval)
        this.board?.destroy?.()
        // Nettoyer les listeners
        if (this._onFenUpdated) this.element.removeEventListener('game-poll:fenUpdated', this._onFenUpdated)
        if (this._onGameUpdated) this.element.removeEventListener('game-poll:gameUpdated', this._onGameUpdated)
        const movesList = document.getElementById('moves-list')
        if (movesList && this._onMoveItemClick) {
            movesList.removeEventListener('click', this._onMoveItemClick)
        }
        if (this._pgnKeyHandler) {
            window.removeEventListener('keydown', this._pgnKeyHandler)
            this._pgnKeyHandler = null
        }
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

        if (!this.validateHandBrainMove(from, originalBoardPos, originalPos)) {
            return
        }

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
        
        // Ne pas désactiver l'interaction ici pour permettre à l'utilisateur de voir le coup
        // et de décider de valider ou d'annuler
        // La validation/annulation sera gérée par les boutons de contrôle
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
            // Sauvegarder l'état actuel pour restauration en cas d'échec
            const previousFen = this.chessJs.fen()
            
            // 1. Mettre à jour l'état local immédiatement pour un retour visuel rapide
            try {
                // Appliquer le coup localement
                const move = this.chessJs.move({
                    from: uci.substring(0, 2),
                    to: uci.substring(2, 4),
                    promotion: uci.length > 4 ? uci.substring(4, 5) : undefined
                });
                
                if (!move) {
                    throw new Error('Coup invalide')
                }
                
                // Mettre à jour l'affichage avec le nouvel état
                this.board.setPosition(this.chessJs.fen(), true)
            } catch (e) {
                console.warn('Erreur lors de la mise à jour locale:', e)
                // On continue quand même avec l'envoi au serveur
            }
            
            // 2. Envoyer le coup au serveur
            const ok = await this.apiPost(`/games/${this.gameIdValue}/move`, { uci })
            if (!ok) { 
                // En cas d'échec, restaurer l'état précédent
                this.chessJs.load(previousFen)
                this.board.setPosition(previousFen, true)
                this.printDebug('❌ Move refusé par le serveur')
                return false
            }
            
            // 3. Mettre à jour l'état avec la réponse du serveur
            try {
                const g = await this.fetchGame()
                console.debug('[game-board] state after move', g)
                
                // Mettre à jour les propriétés du contrôleur
                this.fenValue = g.fen
                this.turnTeamValue = g.turnTeam
                this.deadlineTsValue = g.turnDeadline || 0
                this.statusValue = g.status
                
                // Mettre à jour chess.js et l'affichage
                const newFen = g.fen === 'startpos' ? 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1' : g.fen
                this.chessJs.load(newFen)
                this.board.setPosition(newFen, true)
                
                // Rafraîchir la liste des coups
                await this.reloadMoves()
                this.printDebug('✅ Move OK, FEN mise à jour')
                
                return true
            } catch (e) {
                console.error('Erreur lors de la mise à jour après le coup:', e)
                this.printDebug('⚠️ Erreur lors de la mise à jour après le coup')
                return false
            }
        } catch (error) {
            console.error('Erreur dans sendMove:', error)
            this.printDebug('❌ Erreur lors de l\'envoi du coup')
            return false
        } finally {
            this._submittingMove = false
            this._setPendingDisabled(false)
            
            // Ne pas réactiver l'interaction ici - elle sera gérée par le polling
            // ou par la méthode qui a appelé sendMove
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
        
        // Désactiver l'interaction pendant l'envoi
        this.board.setInteractive(false)
        this._setPendingDisabled(true)
        
        // Afficher un loader pendant la validation côté serveur
        this._showStatusOverlay('Validation du coup…', 'autorenew', true)
        
        try {
            const ok = await this.sendMove(uci)
            if (!ok) {
                // Revenir à l'état précédent si le serveur refuse
                this.chessJs.load(this._pending.prevGameFen)
                this.board.setPosition(this._pending.prevBoardFen, true)
                this.printDebug('↩️ Retour à la position précédente (move refusé)')
                // Réactiver l'interaction
                let canInteract = this.statusValue === 'live' && this.isCurrentPlayerTurn() && this.isTurnReady()
                if (this.isHandBrainMode()) {
                    canInteract = this.computeHandBrainInteractivity(canInteract)
                }
                if (canInteract) {
                    this.board.setInteractive(true)
                }
            } else {
                // Coup accepté par le serveur
                this._hidePendingControls()
                // On garde l'interaction désactivée en attendant le tour suivant
                this.board.setInteractive(false)
                // Mettre à jour le message pour indiquer l'attente de l'adversaire
                this._showStatusOverlay(`En attente de l'adversaire…`, 'hourglass_empty', true)
                this.printDebug('✅ Coup envoyé. En attente de l\'adversaire…')
            }
        } catch (error) {
            console.error('Erreur lors de la confirmation du coup:', error)
            this.printDebug('❌ Erreur lors de l\'envoi du coup')
            // En cas d'erreur, on réactive l'interaction
            const canInteract = this.statusValue === 'live' && this.isCurrentPlayerTurn() && this.isTurnReady()
            if (canInteract) {
                this.board.setInteractive(true)
            }
        } finally {
            this._pending = null
            this._hideStatusOverlay()
            this._hidePendingControls()
        }
    }

    cancelPending() {
        if (!this._pending) return
        this.printDebug(`⛔ Annulation du coup: ${this._pending.uci}`)
        
        // Désactiver l'interaction pendant la restauration
        this.board.setInteractive(false)
        this._setPendingDisabled(true)
        
        try {
            // Restaurer les positions d'origine
            this.chessJs.load(this._pending.prevGameFen)
            this.board.setPosition(this._pending.prevBoardFen, true)
            
            // Réactiver l'interaction uniquement si c'est toujours le tour du joueur
            const canInteract = this.statusValue === 'live' && this.isCurrentPlayerTurn() && this.isTurnReady()
            if (canInteract) {
                // Petit délai pour éviter les interactions non désirées
                setTimeout(() => {
                    this.board.setInteractive(true)
                }, 100)
            }
        } catch (error) {
            console.error('Erreur lors de l\'annulation du coup:', error)
            this.printDebug('❌ Erreur lors de l\'annulation du coup')
        } finally {
            this._pending = null
            this._hidePendingControls()
            this._hideStatusOverlay()
        }
    }

    // ----- Réactions aux événements du polling -----
    onPollFenUpdated(event) {
        const fen = event?.detail?.fen
        if (!fen) return
        
        // Ne pas mettre à jour si on a un coup en attente de validation
        if (this._pending) {
            this.printDebug('ℹ️ Mise à jour FEN ignorée (coup en attente de validation)')
            return
        }
        
        // Mettre à jour les sources: chess.js + canvas
        const normalizedFen = fen === 'startpos' ? 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1' : fen
        
        try {
            // Mettre à jour chess.js
            this.chessJs.load(normalizedFen)
            // Mettre à jour l'affichage
            this.board.setPosition(normalizedFen, true)
        } catch (e) {
            console.error('Erreur lors de la mise à jour de la position:', e)
            this.printDebug('❌ Erreur de mise à jour de la position')
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
        this.updateHandBrainStateFromPayload(gs)
        // Mettre à jour interactivité selon le tour ET le clic "Prêt"
        const isPlayerTurn = this.isCurrentPlayerTurn()
        this.turnReady = this.isTurnReady()
        let canInteract = this.statusValue === 'live' && isPlayerTurn && this.turnReady
        if (this.isHandBrainMode()) {
            canInteract = this.computeHandBrainInteractivity(canInteract)
        }

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

        // 1) Déclencher immédiatement la notification de changement de tour
        try {
            const minimalState = {
                turnTeam: this.turnTeamValue,
                status: this.statusValue,
                currentPlayer: gs.currentPlayer ?? null,
            }
            this.checkForTurnChange(minimalState)
        } catch (_) {}

        // Si la partie est terminée, retirer les overlays, rendre le board visible et désactiver toute interaction
        if (this.statusValue !== 'live') {
            try { this.board.setInteractive(false) } catch (_) {}
            this._hideStatusOverlay()
            const boardEl = this.element.querySelector('#board')
            const canvas = boardEl?.querySelector('canvas')
            if (canvas) canvas.style.visibility = 'visible'
            // Nettoyer tout panneau de décision timeout
            if (this.hasTimeoutDecisionTarget) {
                this.timeoutDecisionTarget.style.display = 'none'
            }
            this.setupBoardOverlay(false, false)
            this.renderState()
            this.renderHandBrainPanel()
            return
        }

        // 2) Puis activer l'interaction (et retirer l'overlay) juste après, pour garantir l'ordre
        //    Utilise requestAnimationFrame pour laisser le navigateur afficher la notif
        const enableInteraction = () => {
            this.board.setInteractive(!!canInteract)
            this.setupBoardOverlay(isPlayerTurn, !!canInteract)
            this.renderState()
            this.renderHandBrainPanel()
        }
        if (typeof window !== 'undefined' && window.requestAnimationFrame) {
            window.requestAnimationFrame(() => enableInteraction())
        } else {
            setTimeout(() => enableInteraction(), 50)
        }
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
        // Mémoriser pour navigation PGN
        this._movesCache = Array.isArray(json.moves) ? json.moves : []
        const list = document.getElementById('moves-list')
        if (!list) return
        list.innerHTML = ''
        const moves = this._movesCache
        for (const m of moves) {
            const li = document.createElement('li')
            li.className = 'move-item slide-up'
            const notation = this.formatMoveNotation(m)
            if (notation === '(?)') {
                console.warn('[game-board] Move ignoré (notation inconnue):', m)
                continue
            }
            const teamName = this.normalizeTeamName(m.team)
            li.setAttribute('data-ply', String(m.ply))
            if (m.fenAfter && typeof m.fenAfter === 'string') {
                li.setAttribute('data-fen-after', m.fenAfter)
            }
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

        // Si la partie est finie, injecter des contrôles de navigation basiques
        if (this.statusValue !== 'live') {
            this.ensurePgnControls()
        }
    }

    async onMoveItemClick(e) {
        const item = e.target?.closest?.('.move-item')
        if (!item) return
        const fen = item.getAttribute('data-fen-after')
        const plyAttr = item.getAttribute('data-ply')
        let fenToLoad = fen
        if (!fenToLoad) {
            // Fallback: récupérer la liste complète et trouver le FEN
            try {
                const res = await fetch(`/games/${this.gameIdValue}/moves`, { headers: { 'Accept': 'application/json' } })
                if (res.ok) {
                    const json = await res.json()
                    const moves = Array.isArray(json.moves) ? json.moves : []
                    const target = moves.find(m => String(m.ply) === String(plyAttr))
                    fenToLoad = target?.fenAfter || null
                    if (fenToLoad) {
                        item.setAttribute('data-fen-after', fenToLoad)
                    }
                }
            } catch (_) {}
        }
        if (!fenToLoad || typeof fenToLoad !== 'string') return

        // Charger la position sur chess.js et le canvas
        this.chessJs.load(fenToLoad === 'startpos' ? 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1' : fenToLoad)
        this.board.setPosition(fenToLoad === 'startpos' ? 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1' : fenToLoad)

        // Mettre en surbrillance l'élément sélectionné
        try {
            const list = document.getElementById('moves-list')
            list?.querySelectorAll?.('.move-item.active')?.forEach(el => el.classList.remove('active'))
            item.classList.add('active')
        } catch (_) {}
    }

    ensurePgnControls() {
        const container = document.getElementById('moves-list')?.parentElement
        if (!container) return
        if (container.querySelector('.pgn-controls')) return
        const controls = document.createElement('div')
        controls.className = 'pgn-controls'
        controls.style.cssText = 'display:flex;gap:8px;justify-content:center;margin-top:8px;'
        controls.innerHTML = `
            <button type="button" class="neo-btn neo-btn-secondary neo-btn-sm" data-role="pgn-prev">◀️ Précédent</button>
            <button type="button" class="neo-btn neo-btn-secondary neo-btn-sm" data-role="pgn-next">Suivant ▶️</button>
        `
        container.appendChild(controls)
        controls.querySelector('[data-role="pgn-prev"]').addEventListener('click', () => this.prevMove())
        controls.querySelector('[data-role="pgn-next"]').addEventListener('click', () => this.nextMove())

        // Support touches clavier ← →
        if (!this._pgnKeyHandler) {
            this._pgnKeyHandler = (ev) => {
                if (this.statusValue === 'live') return
                if (ev.key === 'ArrowLeft') { this.prevMove() }
                if (ev.key === 'ArrowRight') { this.nextMove() }
            }
            window.addEventListener('keydown', this._pgnKeyHandler)
        }
    }

    getActivePly() {
        const list = document.getElementById('moves-list')
        const active = list?.querySelector('.move-item.active')
        if (active) {
            const p = parseInt(active.getAttribute('data-ply') || '0', 10)
            if (!Number.isNaN(p)) return p
        }
        // Si aucun actif, retourner le dernier ply
        return (this._movesCache && this._movesCache.length) ? this._movesCache[this._movesCache.length - 1].ply : 0
    }

    goToPly(ply) {
        const list = document.getElementById('moves-list')
        if (!list) return
        const item = list.querySelector(`.move-item[data-ply="${ply}"]`)
        if (!item) return
        const fen = item.getAttribute('data-fen-after')
        if (fen) {
            this.chessJs.load(fen === 'startpos' ? 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1' : fen)
            this.board.setPosition(fen === 'startpos' ? 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1' : fen)
        }
        list.querySelectorAll('.move-item.active').forEach(el => el.classList.remove('active'))
        item.classList.add('active')
        item.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
    }

    nextMove() {
        if (!this._movesCache || !this._movesCache.length) return
        const current = this.getActivePly()
        const idx = this._movesCache.findIndex(m => m.ply === current)
        const next = (idx >= 0 && idx < this._movesCache.length - 1) ? this._movesCache[idx + 1].ply : current
        if (next !== current) this.goToPly(next)
    }

    prevMove() {
        if (!this._movesCache || !this._movesCache.length) return
        const current = this.getActivePly()
        const idx = this._movesCache.findIndex(m => m.ply === current)
        const prev = (idx > 0) ? this._movesCache[idx - 1].ply : current
        if (prev !== current) this.goToPly(prev)
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
    
    setupBoardOverlay(isPlayerTurn, canInteract) {
        const boardEl = this.element.querySelector('#board')
        if (!boardEl) return

        const existingOverlay = boardEl.querySelector('.board-overlay')
        if (existingOverlay) {
            existingOverlay.remove()
        }

        const canvas = boardEl.querySelector('canvas')
        if (canvas) {
            const shouldShow =
                this.statusValue === 'live' &&
                (!!canInteract || this.shouldKeepCanvasVisibleWhenInactive())
            canvas.style.visibility = shouldShow ? 'visible' : 'hidden'
        }

        if (this.statusValue !== 'live') {
            return
        }

        if (!isPlayerTurn) {
            this.renderBoardOverlay(boardEl, {
                icon: 'hourglass_empty',
                title: 'En attente...',
                text: "C'est au tour de l'adversaire",
                spinning: true,
            })
            return
        }

        if (!this.turnReady) {
            this.renderReadyOverlay(boardEl)
            return
        }

        if (this.isHandBrainMode()) {
            const overlay = this.resolveHandBrainOverlay(canInteract)
            if (overlay) {
                this.renderBoardOverlay(boardEl, overlay)
                return
            }
        }

        if (!canInteract) {
            this.renderBoardOverlay(boardEl, {
                icon: 'hourglass_bottom',
                title: 'Patientez…',
                text: 'Une action est en cours pour ce tour.',
                spinning: true,
            })
        }
    }

    renderBoardOverlay(boardEl, { icon, title, text, button, spinning, backgroundOpacity } = {}) {
        const overlay = document.createElement('div')
        overlay.className = 'board-overlay'
        overlay.innerHTML = `
            <div class="overlay-content">
                <div class="waiting-message">
                    <i class="material-icons">${icon || 'hourglass_empty'}</i>
                    ${title ? `<h3>${title}</h3>` : ''}
                    ${text ? `<p>${text}</p>` : ''}
                    ${button ? `<button type="button" class="neo-btn neo-btn-primary neo-btn-lg" data-role="overlay-button">${button}</button>` : ''}
                </div>
            </div>
        `

        const opacity = this.resolveOverlayBackgroundOpacity(backgroundOpacity)

        overlay.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, ${opacity});
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: 8px;
            backdrop-filter: blur(2px);
        `

        const content = overlay.querySelector('.overlay-content')
        if (content) {
            content.style.cssText = 'text-align:center;color:white;padding:2rem;'
        }

        const iconEl = overlay.querySelector('.material-icons')
        if (iconEl) {
            iconEl.style.cssText = `font-size:3rem;margin-bottom:1rem;opacity:0.85;${spinning ? 'animation: spin 2s linear infinite;' : ''}`
        }

        if (spinning && !document.querySelector('#board-overlay-styles')) {
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
        return overlay
    }

    renderReadyOverlay(boardEl) {
        const overlay = this.renderBoardOverlay(boardEl, {
            icon: 'bolt',
            title: 'Votre tour',
            text: 'Cliquez sur « Prêt » pour révéler l\'échiquier et jouer votre coup',
            button: 'Prêt',
        })
        const button = overlay?.querySelector('[data-role="overlay-button"]')
        if (button) {
            button.addEventListener('click', () => {
                this.setTurnReady()
                this.board.setInteractive(true)
                const canvas = boardEl.querySelector('canvas')
                if (canvas) {
                    canvas.style.visibility = 'visible'
                }
                overlay.remove()
            })
        }
    }

    resolveHandBrainOverlay(canInteract) {
        if (!this.isHandBrainMode()) {
            return null
        }
        if (canInteract) {
            return null
        }

        const state = this.handBrainState || {}
        const isBrain = this.isCurrentUserBrain()
        const isHand = this.isCurrentUserHand()

        if (state.currentRole === 'brain') {
            if (isBrain) {
                return {
                    icon: 'psychology',
                    title: 'Choisissez une pièce',
                    text: 'Sélectionnez un type de pièce dans le panneau Hand-Brain pour guider votre coéquipier.',
                    backgroundOpacity: 0.4,
                }
            }

                return {
                    icon: 'psychology',
                    title: 'Indice en préparation',
                    text: 'Le cerveau de votre équipe choisit une pièce.',
                    spinning: true,
                }
        }

        if (state.currentRole === 'hand') {
            const required = this.getHandBrainPieceCode()
            if (!required) {
                if (isHand) {
                    return {
                        icon: 'psychology_alt',
                        title: 'Attendez l\'indice',
                        text: 'Le cerveau doit choisir une pièce avant votre coup.',
                        spinning: true,
                    }
                }

                return {
                    icon: 'psychology_alt',
                    title: 'En attente de l\'indice',
                    text: 'Le cerveau doit sélectionner une pièce avant le prochain coup.',
                    spinning: true,
                }
            }

            if (!isHand) {
                return {
                    icon: 'front_hand',
                    title: 'Main désignée',
                    text: 'La main de votre équipe prépare le coup demandé.',
                }
            }
        }

        return null
    }
    isHandBrainMode() {
        return (this.modeValue || '').toLowerCase() === 'hand_brain'
    }

    getMembershipId() {
        return this.membershipIdValue || ''
    }

    isCurrentUserBrain() {
        if (!this.isHandBrainMode()) return false
        const id = this.getMembershipId()
        if (!id) return false
        return !!this.handBrainState?.brainMemberId && id === this.handBrainState.brainMemberId
    }

    isCurrentUserHand() {
        if (!this.isHandBrainMode()) return false
        const id = this.getMembershipId()
        if (!id) return false
        return !!this.handBrainState?.handMemberId && id === this.handBrainState.handMemberId
    }

    getHandBrainPieceCode() {
        const hint = this.handBrainState?.pieceHint
        if (!hint) return null
        const map = {
            pawn: 'p',
            knight: 'n',
            bishop: 'b',
            rook: 'r',
            queen: 'q',
            king: 'k',
        }
        return map[String(hint).toLowerCase()] || null
    }

    describeHandBrainPiece(code) {
        const labels = {
            p: 'pion',
            n: 'cavalier',
            b: 'fou',
            r: 'tour',
            q: 'dame',
            k: 'roi',
        }
        return labels[code] || 'pièce'
    }

    computeHandBrainInteractivity(base) {
        if (!base) return false
        if (!this.isHandBrainMode()) return base

        const state = this.handBrainState || {}
        if (state.currentRole === 'brain') {
            return false
        }

        if (state.currentRole === 'hand') {
            if (!this.isCurrentUserHand()) {
                return false
            }
            if (!state.pieceHint) {
                return false
            }
            return true
        }

        return false
    }

    shouldKeepCanvasVisibleWhenInactive() {
        if (!this.isHandBrainMode()) {
            return false
        }

        const state = this.handBrainState || {}
        if (state.currentRole !== 'brain') {
            return false
        }

        return this.isCurrentUserBrain()
    }

    resolveOverlayBackgroundOpacity(requestedOpacity) {
        if (typeof requestedOpacity === 'number') {
            return requestedOpacity
        }

        if (this.shouldKeepCanvasVisibleWhenInactive()) {
            return 0.4
        }

        return 1
    }

    updateHandBrainStateFromPayload(payload) {
        if (!this.isHandBrainMode()) {
            return
        }
        if (!payload || typeof payload !== 'object') {
            return
        }

        let source = null
        if (payload.handBrain && typeof payload.handBrain === 'object') {
            source = payload.handBrain
        } else if (
            Object.prototype.hasOwnProperty.call(payload, 'currentRole') ||
            Object.prototype.hasOwnProperty.call(payload, 'pieceHint') ||
            Object.prototype.hasOwnProperty.call(payload, 'brainMemberId') ||
            Object.prototype.hasOwnProperty.call(payload, 'handMemberId')
        ) {
            source = payload
        } else if (
            Object.prototype.hasOwnProperty.call(payload, 'handBrainCurrentRole') ||
            Object.prototype.hasOwnProperty.call(payload, 'handBrainPieceHint') ||
            Object.prototype.hasOwnProperty.call(payload, 'handBrainBrainMemberId') ||
            Object.prototype.hasOwnProperty.call(payload, 'handBrainHandMemberId')
        ) {
            source = {
                currentRole: payload.handBrainCurrentRole,
                pieceHint: payload.handBrainPieceHint,
                brainMemberId: payload.handBrainBrainMemberId,
                handMemberId: payload.handBrainHandMemberId,
            }
        }

        if (!source) {
            return
        }

        const normalized = {
            currentRole: source.currentRole ?? null,
            pieceHint: source.pieceHint ?? null,
            brainMemberId: source.brainMemberId ?? null,
            handMemberId: source.handMemberId ?? null,
        }

        const prev = this.handBrainState || {}
        const changed = ['currentRole', 'pieceHint', 'brainMemberId', 'handMemberId'].some(
            key => (prev[key] || null) !== (normalized[key] || null)
        )

        this.handBrainState = normalized

        if (changed) {
            this.renderHandBrainPanel()
        }
    }

    renderHandBrainPanel() {
        if (!this.hasHandBrainPanelTarget) return

        const active = this.isHandBrainMode()
        this.handBrainPanelTarget.style.display = active ? '' : 'none'
        if (!active) {
            return
        }

        const state = this.handBrainState || {}
        const pieceCode = this.getHandBrainPieceCode()
        const hintLabel = pieceCode ? this.describeHandBrainPiece(pieceCode) : '—'

        if (this.hasHandBrainPhaseTarget) {
            let phase = 'Mode Hand-Brain'
            if (this.statusValue !== 'live') {
                phase = 'Mode inactif'
            } else if (state.currentRole === 'brain') {
                phase = 'Phase cerveau'
            } else if (state.currentRole === 'hand' && pieceCode) {
                phase = `Phase main – ${hintLabel}`
            } else if (state.currentRole === 'hand') {
                phase = 'En attente de l\'indice'
            } else {
                phase = 'En attente de l\'activation'
            }
            this.handBrainPhaseTarget.textContent = phase
        }

        if (this.hasHandBrainHintTarget) {
            this.handBrainHintTarget.textContent = pieceCode ? hintLabel : '—'
        }

        if (this.hasHandBrainRoleBrainTarget) {
            const brainValue = state.brainMemberId ? (this.isCurrentUserBrain() ? 'Vous' : 'Coéquipier') : '—'
            this.handBrainRoleBrainTarget.querySelector('.role-value').textContent = brainValue
            this.handBrainRoleBrainTarget.classList.toggle('is-you', this.isCurrentUserBrain())
        }

        if (this.hasHandBrainRoleHandTarget) {
            const handValue = state.handMemberId ? (this.isCurrentUserHand() ? 'Vous' : 'Coéquipier') : '—'
            this.handBrainRoleHandTarget.querySelector('.role-value').textContent = handValue
            this.handBrainRoleHandTarget.classList.toggle('is-you', this.isCurrentUserHand())
        }

        const shouldEnableButtons = this.isCurrentUserBrain() && state.currentRole === 'brain' && !this.handBrainHintLoading
        if (this.hasHandBrainPieceButtonTarget) {
            this.handBrainPieceButtonTargets.forEach(btn => {
                const btnPiece = btn.dataset.piece || ''
                const isSelected = state.pieceHint && state.pieceHint === btnPiece
                btn.classList.toggle('is-active', !!isSelected)
                btn.setAttribute('aria-pressed', isSelected ? 'true' : 'false')
                btn.disabled = !shouldEnableButtons
            })
        }

        this.handBrainPanelTarget.dataset.phase = state.currentRole || 'idle'
    }

    setHandBrainButtonsDisabled(disabled) {
        if (!this.hasHandBrainPieceButtonTarget) return
        this.handBrainPieceButtonTargets.forEach(btn => {
            btn.disabled = !!disabled
        })
    }

    async selectHandBrainPiece(event) {
        const piece = event?.currentTarget?.dataset?.piece
        if (!piece) return
        if (!this.isHandBrainMode()) return
        if (!this.isCurrentUserBrain()) {
            this.printDebug('⚠️ Seul le cerveau peut sélectionner une pièce.')
            return
        }
        if (this.handBrainState?.currentRole !== 'brain') {
            this.printDebug('🧠 L\'indice a déjà été défini pour ce tour.')
            return
        }
        if (this.handBrainHintLoading) {
            return
        }

        this.handBrainHintLoading = true
        this.setHandBrainButtonsDisabled(true)

        try {
            const res = await fetch(`/games/${this.gameIdValue}/hand-brain/hint`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ piece }),
            })

            if (!res.ok) {
                let message = res.statusText
                try {
                    const data = await res.json()
                    message = data?.message || message
                } catch (_) {}
                this.printDebug('❌ Indice refusé: ' + message)
                return
            }

            const data = await res.json()
            this.updateHandBrainStateFromPayload(data)
            this.printDebug(`🧠 Indice envoyé: ${piece}`)
        } catch (error) {
            console.error('[hand-brain] hint error', error)
            this.printDebug('❌ Impossible d\'envoyer l\'indice Hand-Brain')
        } finally {
            this.handBrainHintLoading = false
            this.renderHandBrainPanel()
        }
    }

    validateHandBrainMove(from, originalBoardPos, originalFen) {
        if (!this.isHandBrainMode()) {
            return true
        }

        const state = this.handBrainState || {}
        if (state.currentRole !== 'hand') {
            if (this.isCurrentUserBrain()) {
                this.printDebug('🧠 Sélectionnez une pièce dans le panneau Hand-Brain avant de jouer.')
            } else if (this.isCurrentUserHand()) {
                this.printDebug('🧠 Attendez l\'indice du cerveau avant de bouger une pièce.')
            } else {
                this.printDebug('🧠 En attente du duo Hand-Brain.')
            }
            this.board.setPosition(originalBoardPos, true)
            return false
        }

        if (!this.isCurrentUserHand()) {
            this.printDebug('🤚 Seule la main désignée peut jouer ce coup.')
            this.board.setPosition(originalBoardPos, true)
            return false
        }

        const requiredType = this.getHandBrainPieceCode()
        if (!requiredType) {
            this.printDebug('🧠 Attendez que le cerveau sélectionne une pièce.')
            this.board.setPosition(originalBoardPos, true)
            return false
        }

        const piece = this.chessJs.get(from)
        if (!piece) {
            this.printDebug('❌ Case vide: choisissez une pièce correspondant à l\'indice.')
            this.board.setPosition(originalBoardPos, true)
            return false
        }

        if (piece.type !== requiredType) {
            const label = this.describeHandBrainPiece(requiredType)
            this.printDebug(`❌ Le cerveau a demandé un ${label}.`)
            this.board.setPosition(originalBoardPos, true)
            return false
        }

        return true
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
