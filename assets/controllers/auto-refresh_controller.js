import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static values = { 
        interval: { type: Number, default: 5000 }, // 5 secondes par défaut
        url: String, // URL à rafraîchir (optionnel, par défaut = page actuelle)
        target: String, // Sélecteur CSS de l'élément à actualiser (optionnel, par défaut = element actuel)
        turbo: { type: Boolean, default: true }, // Utiliser Turbo pour l'actualisation
        paused: { type: Boolean, default: false } // Pause l'actualisation
    }

    connect() {
        console.log('🔄 Auto-refresh générique activé')
        this.originalContent = this.element.innerHTML
        this.startRefresh()
        
        // Gérer la visibilité de la page
        document.addEventListener('visibilitychange', this.handleVisibilityChange.bind(this))
        
        // Ajouter les contrôles si demandés
        this.addControls()
    }

    disconnect() {
        this.stopRefresh()
        document.removeEventListener('visibilitychange', this.handleVisibilityChange.bind(this))
    }

    startRefresh() {
        if (this.refreshInterval || this.pausedValue) return
        
        this.refreshInterval = setInterval(() => {
            this.refresh()
        }, this.intervalValue)
        
        console.log(`⏱️ Auto-refresh démarré toutes les ${this.intervalValue}ms`)
    }

    stopRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval)
            this.refreshInterval = null
            console.log('⏹️ Auto-refresh arrêté')
        }
    }

    async refresh() {
        if (this.pausedValue) return

        try {
            const url = this.urlValue || window.location.href
            const response = await fetch(url, {
                headers: {
                    'Accept': this.turboValue ? 'text/vnd.turbo-stream.html, text/html' : 'text/html',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })

            if (!response.ok) {
                console.warn('⚠️ Erreur lors de l\'actualisation:', response.status)
                return
            }

            if (this.turboValue && response.headers.get('Content-Type')?.includes('turbo-stream')) {
                // Traitement Turbo Stream
                const turboStream = await response.text()
                this.processTurboStream(turboStream)
            } else {
                // Actualisation HTML classique
                const html = await response.text()
                this.updateContent(html)
            }

            this.dispatch('refreshed', { detail: { url, timestamp: Date.now() } })

        } catch (error) {
            console.error('❌ Erreur lors de l\'actualisation automatique:', error)
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
        this.stopRefresh()
        if (!this.pausedValue) {
            this.startRefresh()
        }
        console.log(`⏰ Intervalle d'actualisation changé : ${newInterval}ms`)
    }
}
