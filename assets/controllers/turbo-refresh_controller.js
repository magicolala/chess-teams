import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static values = { 
        streamUrl: String,
        interval: { type: Number, default: 3000 },
        enabled: { type: Boolean, default: true }
    }
    static targets = ['status']

    connect() {
        console.log('⚡ Turbo Streams auto-refresh activé')
        if (this.enabledValue) {
            this.startStreaming()
        }
        
        // Gérer la visibilité de la page
        document.addEventListener('visibilitychange', this.handleVisibilityChange.bind(this))
    }

    disconnect() {
        this.stopStreaming()
        document.removeEventListener('visibilitychange', this.handleVisibilityChange.bind(this))
    }

    startStreaming() {
        if (this.streamInterval) return
        
        this.streamInterval = setInterval(() => {
            this.fetchTurboStream()
        }, this.intervalValue)
        
        console.log(`⚡ Turbo Streams démarré toutes les ${this.intervalValue}ms`)
        this.updateStatus('🟢 Connecté')
    }

    stopStreaming() {
        if (this.streamInterval) {
            clearInterval(this.streamInterval)
            this.streamInterval = null
            console.log('⏹️ Turbo Streams arrêté')
            this.updateStatus('🔴 Déconnecté')
        }
    }

    async fetchTurboStream() {
        if (!this.streamUrlValue) {
            console.warn('⚠️ Aucune URL de stream Turbo configurée')
            return
        }

        try {
            const response = await fetch(this.streamUrlValue, {
                headers: {
                    'Accept': 'text/vnd.turbo-stream.html',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })

            if (!response.ok) {
                console.warn('⚠️ Erreur lors de la récupération du Turbo Stream:', response.status)
                this.updateStatus('🟡 Erreur')
                return
            }

            const contentType = response.headers.get('Content-Type')
            if (contentType && contentType.includes('turbo-stream')) {
                const turboStreamHTML = await response.text()
                this.processTurboStream(turboStreamHTML)
                this.updateStatus('🟢 Mis à jour')
            }

        } catch (error) {
            console.error('❌ Erreur Turbo Stream:', error)
            this.updateStatus('🔴 Erreur')
        }
    }

    processTurboStream(turboStreamHTML) {
        if (!turboStreamHTML.trim()) return

        try {
            // Utiliser l'API Turbo pour traiter le stream
            if (window.Turbo && window.Turbo.renderStreamMessage) {
                window.Turbo.renderStreamMessage(turboStreamHTML)
                console.log('✅ Turbo Stream traité avec succès')
            } else {
                // Fallback : traitement manuel
                this.manuallyProcessStream(turboStreamHTML)
            }
            
            this.dispatch('streamProcessed', { detail: { html: turboStreamHTML } })
            
        } catch (error) {
            console.error('❌ Erreur lors du traitement du Turbo Stream:', error)
        }
    }

    manuallyProcessStream(turboStreamHTML) {
        // Traitement manuel des Turbo Streams en cas de problème avec l'API
        const parser = new DOMParser()
        const doc = parser.parseFromString(turboStreamHTML, 'text/html')
        const streams = doc.querySelectorAll('turbo-stream')
        
        streams.forEach(stream => {
            const action = stream.getAttribute('action')
            const target = stream.getAttribute('target')
            const template = stream.querySelector('template')
            
            if (!target || !template) return
            
            const targetElement = document.getElementById(target)
            if (!targetElement) return
            
            switch (action) {
                case 'replace':
                    targetElement.outerHTML = template.innerHTML
                    break
                case 'update':
                    targetElement.innerHTML = template.innerHTML
                    break
                case 'append':
                    targetElement.insertAdjacentHTML('beforeend', template.innerHTML)
                    break
                case 'prepend':
                    targetElement.insertAdjacentHTML('afterbegin', template.innerHTML)
                    break
                case 'remove':
                    targetElement.remove()
                    break
            }
        })
    }

    updateStatus(status) {
        if (this.hasStatusTarget) {
            this.statusTarget.textContent = status
        }
    }

    handleVisibilityChange() {
        if (document.hidden) {
            this.stopStreaming()
        } else {
            if (this.enabledValue) {
                this.startStreaming()
                // Fetch immédiat au retour
                this.fetchTurboStream()
            }
        }
    }

    // Actions manuelles
    toggle() {
        this.enabledValue = !this.enabledValue
        
        if (this.enabledValue) {
            this.startStreaming()
        } else {
            this.stopStreaming()
        }
    }

    forceRefresh() {
        console.log('⚡ Fetch Turbo Stream forcé')
        this.fetchTurboStream()
    }

    changeInterval(event) {
        const newInterval = parseInt(event.target.value) * 1000
        this.intervalValue = newInterval
        this.stopStreaming()
        if (this.enabledValue) {
            this.startStreaming()
        }
        console.log(`⏰ Intervalle Turbo Stream changé : ${newInterval}ms`)
    }
}
