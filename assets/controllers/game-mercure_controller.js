import { Controller } from '@hotwired/stimulus'

// Subscribes to Mercure updates for a game and dispatches UI events
// Expects:
// - data-game-mercure-url-value: the Mercure subscription URL with topic for this game
// - data-game-mercure-game-id-value: the game id
export default class extends Controller {
    static values = { url: String, gameId: String }

    connect() {
        // Flag to prevent updates during drag-and-drop
        this.isDragging = false
        this.element.addEventListener('game-board:drag-start', () => {
            console.debug('[mercure] Drag detected, updates paused')
            this.isDragging = true
        })
        this.element.addEventListener('game-board:drag-end', () => {
            console.debug('[mercure] Drag ended, resuming updates')
            this.isDragging = false
            // Refresh state immediately after drop
            this.refreshState()
        })

        if (!window.EventSource) {
            console.warn('[mercure] EventSource non supporté par ce navigateur')
            return
        }
        if (!this.urlValue) {
            console.warn('[mercure] URL d\'abonnement manquante')
            return
        }

        try {
            this.es = new EventSource(this.urlValue, { withCredentials: false })

            this.es.onopen = () => {
                console.log('[mercure] Connecté au hub')
                // Inform other controllers they can stop polling
                this.dispatch('connected', { detail: { gameId: this.gameIdValue } })
            }

            this.es.onmessage = (event) => {
                // Do not process update if a piece is being dragged
                if (this.isDragging) {
                    console.debug('[mercure] Update ignored (drag in progress)')
                    return
                }

                let data = null
                try {
                    data = JSON.parse(event.data || '{}')
                } catch (e) {
                    console.debug('[mercure] message non JSON, ignore le parse et garde fallback')
                }

                if (data && typeof data === 'object') {
                    // 1) Mettre à jour l'échiquier si une FEN est fournie
                    if (data.fen) {
                        this.element.dispatchEvent(new CustomEvent('game-poll:fenUpdated', { detail: { fen: data.fen }, bubbles: true }))
                    }

                    // 2) Dispatche un événement d'état avec les champs disponibles
                    //    game-poll_controller met à jour ce qu'il peut en fonction des clés présentes
                    this.element.dispatchEvent(new CustomEvent('game-poll:gameUpdated', { detail: data, bubbles: true }))

                    // 3) Rien d'autre à faire ici: on évite les requêtes HTTP supplémentaires
                    return
                }

                // Fallback si le payload est inexploitable
                this.refreshState()
            }

            this.es.onerror = (e) => {
                console.warn('[mercure] erreur EventSource, garde le polling comme secours', e)
            }
        } catch (e) {
            console.error('[mercure] échec d\'initialisation', e)
        }
    }

    disconnect() {
        try { this.es?.close?.() } catch (_) {}
        this.es = null
    }

    async refreshState() {
        const gameId = this.gameIdValue
        if (!gameId) return
        try {
            // 1) Appel léger pour état minimal & FEN
            const lightRes = await fetch(`/games/${gameId}`, { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
            if (lightRes.ok) {
                const light = await lightRes.json()
                if (light?.fen) {
                    // align with game-poll events so board updates seamlessly
                    this.element.dispatchEvent(new CustomEvent('game-poll:fenUpdated', { detail: { fen: light.fen }, bubbles: true }))
                }
            }

            // 2) Appel complet pour informations riches
            const stateRes = await fetch(`/games/${gameId}/state`, { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
            if (stateRes.ok) {
                const state = await stateRes.json()
                this.element.dispatchEvent(new CustomEvent('game-poll:gameUpdated', { detail: state, bubbles: true }))
            }
        } catch (e) {
            console.error('[mercure] échec refreshState', e)
        }
    }
}
