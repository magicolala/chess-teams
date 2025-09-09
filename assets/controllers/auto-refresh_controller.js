import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static values = { 
        interval: { type: Number, default: 5000 }, // 5 secondes par défaut
        url: String, // URL à rafraîchir (optionnel, par défaut = page actuelle)
        target: String, // Sélecteur CSS de l'élément à actualiser (optionnel, par défaut = element actuel)
        turbo: { type: Boolean, default: true }, // Utiliser Turbo pour l'actualisation
        paused: { type: Boolean, default: false } // Pause l'actualisation
    }

    initialize() {
        // Binder une seule fois pour pouvoir retirer correctement les listeners
        this._onVisibilityChange = this.handleVisibilityChange.bind(this)
        this._refreshTimeout = null
        this._backoff = null
        this._etag = null
        this._lastModified = null
        this._inFlight = false
        this._abortController = null
    }

    connect() {
        console.log('🔄 Auto-refresh générique activé')
        this.originalContent = this.element.innerHTML
        this.startRefresh()
        
        // Gérer la visibilité de la page
        document.addEventListener('visibilitychange', this._onVisibilityChange)
        
        // Ajouter les contrôles si demandés
        this.addControls()
    }

    disconnect() {
        this.stopRefresh()
        document.removeEventListener('visibilitychange', this._onVisibilityChange)
        if (this._abortController) {
            this._abortController.abort()
            this._abortController = null
        }
    }

    startRefresh() {
        if (this._refreshTimeout || this.pausedValue) return
        this._scheduleNext(0)
        console.log(`⏱️ Auto-refresh démarré (intervalle de base ${this.intervalValue}ms)`)        
    }

    stopRefresh() {
        if (this._refreshTimeout) {
            clearTimeout(this._refreshTimeout)
            this._refreshTimeout = null
        }
        this._backoff = null
        console.log('⏹️ Auto-refresh arrêté')
    }

    async refresh() {
        if (this.pausedValue) return
        if (document.hidden) return // Ne pas rafraîchir onglet en arrière-plan
        if (this._inFlight) {
            // Éviter les requêtes concurrentes: on annule la précédente et on relance
            try { this._abortController?.abort() } catch (_) {}
        }

        try {
            const url = this.urlValue || window.location.href
            this._abortController = new AbortController()
            this._inFlight = true
            const headers = {
                'Accept': this.turboValue ? 'text/vnd.turbo-stream.html, text/html' : 'text/html',
                'X-Requested-With': 'XMLHttpRequest'
            }
            if (this._etag) headers['If-None-Match'] = this._etag
            if (this._lastModified) headers['If-Modified-Since'] = this._lastModified

            const response = await fetch(url, {
                headers: {
                    ...headers
                },
                signal: this._abortController.signal,
                cache: 'no-store'
            })

            // Gestion 304 Not Modified
            if (response.status === 304) {
                this._onSuccess()
                this.dispatch('refreshed', { detail: { url, timestamp: Date.now(), status: 304 } })
                return
            }

            if (!response.ok) {
                console.warn('⚠️ Erreur lors de l\'actualisation:', response.status)
                this._onError()
                return
            }

            // Stocker ETag/Last-Modified pour les prochains appels
            const etag = response.headers.get('ETag')
            const lastMod = response.headers.get('Last-Modified')
            if (etag) this._etag = etag
            if (lastMod) this._lastModified = lastMod

            if (this.turboValue && response.headers.get('Content-Type')?.includes('turbo-stream')) {
                // Traitement Turbo Stream
                const turboStream = await response.text()
                this.processTurboStream(turboStream)
            } else {
                // Actualisation HTML classique
                const html = await response.text()
                this.updateContent(html)
            }

            this._onSuccess()
            this.dispatch('refreshed', { detail: { url, timestamp: Date.now(), status: response.status } })

        } catch (error) {
            if (error?.name === 'AbortError') {
                // Ignorer: une nouvelle requête a été planifiée
                console.debug('↪️ Requête d\'actualisation annulée (AbortController)')
            } else {
                console.error('❌ Erreur lors de l\'actualisation automatique:', error)
                this._onError()
            }
        } finally {
            this._inFlight = false
        }
    }

    updateContent(html) {
        const parser = new DOMParser()
        const doc = parser.parseFromString(html, 'text/html')
        
        if (this.targetValue) {
            // Actualiser un élément spécifique
            const newElement = doc.querySelector(this.targetValue)
            const currentElement = document.querySelector(this.targetValue)
            
            if (newElement && currentElement) {
                currentElement.innerHTML = newElement.innerHTML
            }
        } else {
            // Actualiser l'élément actuel
            const newContent = doc.querySelector(`[data-controller~="auto-refresh"]`)
            if (newContent && newContent.innerHTML !== this.element.innerHTML) {
                this.element.innerHTML = newContent.innerHTML
            }
        }
    }

    processTurboStream(turboStreamHTML) {
        // Traiter les Turbo Streams
        const streamElement = document.createElement('div')
        streamElement.innerHTML = turboStreamHTML
        
        // Déclencher le traitement Turbo
        if (window.Turbo) {
            window.Turbo.renderStreamMessage(turboStreamHTML)
        }
    }

    handleVisibilityChange() {
        if (document.hidden) {
            this.stopRefresh()
        } else {
            this.startRefresh()
            // Actualisation immédiate au retour
            this.refresh()
        }
    }

    addControls() {
        if (!this.element.querySelector('.auto-refresh-controls')) {
            const controls = document.createElement('div')
            controls.className = 'auto-refresh-controls neo-flex neo-gap-sm neo-mb-sm'
            controls.innerHTML = `
                <button class="neo-btn neo-btn-sm neo-btn-secondary" data-action="auto-refresh#forceRefresh">
                    🔄 Actualiser
                </button>
                <button class="neo-btn neo-btn-sm neo-btn-secondary" data-action="auto-refresh#togglePause">
                    <span data-auto-refresh-target="pauseButton">⏸️ Pause</span>
                </button>
                <select class="neo-select neo-select-sm" data-action="auto-refresh#changeInterval">
                    <option value="1">1s</option>
                    <option value="2" ${this.intervalValue === 2000 ? 'selected' : ''}>2s</option>
                    <option value="5" ${this.intervalValue === 5000 ? 'selected' : ''}>5s</option>
                    <option value="10" ${this.intervalValue === 10000 ? 'selected' : ''}>10s</option>
                    <option value="30" ${this.intervalValue === 30000 ? 'selected' : ''}>30s</option>
                </select>
            `
            
            this.element.insertBefore(controls, this.element.firstChild)
        }
    }

    // Actions manuelles
    forceRefresh() {
        console.log('🔄 Actualisation forcée')
        // Réinitialiser le backoff et rafraîchir immédiatement
        this._backoff = null
        this.refresh()
    }

    togglePause() {
        this.pausedValue = !this.pausedValue
        const button = this.element.querySelector('[data-auto-refresh-target="pauseButton"]')
        
        if (this.pausedValue) {
            this.stopRefresh()
            if (button) button.textContent = '▶️ Reprendre'
            console.log('⏸️ Auto-refresh mis en pause')
        } else {
            this.startRefresh()
            if (button) button.textContent = '⏸️ Pause'
            console.log('▶️ Auto-refresh repris')
        }
    }

    changeInterval(event) {
        const newInterval = parseInt(event.target.value) * 1000
        this.intervalValue = newInterval
        // Redémarrer le scheduler avec le nouvel intervalle
        this.stopRefresh()
        if (!this.pausedValue) {
            this.startRefresh()
        }
        console.log(`⏰ Intervalle d'actualisation changé : ${newInterval}ms`)
    }

    // ----- Helpers privés -----
    _scheduleNext(delayMs) {
        if (this._refreshTimeout) {
            clearTimeout(this._refreshTimeout)
        }
        const base = typeof delayMs === 'number' ? delayMs : this._computeNextDelay()
        const jitter = Math.floor(base * 0.1 * Math.random()) // 0-10% de jitter pour éviter le thundering herd
        const next = Math.max(200, base + jitter)
        this._refreshTimeout = setTimeout(() => {
            this._refreshTimeout = null
            this.refresh()
            // La planification suivante est déclenchée dans _onSuccess/_onError
        }, next)
    }

    _computeNextDelay() {
        if (this._backoff) return this._backoff
        return this.intervalValue
    }

    _onSuccess() {
        // Réinitialiser le backoff en cas de succès et planifier le prochain tick
        this._backoff = null
        this._scheduleNext(this.intervalValue)
    }

    _onError() {
        // Exponential backoff avec plafond 60s
        const base = this._backoff ?? this.intervalValue
        this._backoff = Math.min(base * 2, 60000)
        this._scheduleNext(this._backoff)
    }
}
