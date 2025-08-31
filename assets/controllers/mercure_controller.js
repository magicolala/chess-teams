import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static values = { 
        hubUrl: String,
        topic: String,
        jwt: String,
        autoConnect: { type: Boolean, default: true }
    }
    static targets = ['status', 'messages']

    connect() {
        console.log('📡 Mercure controller activé')
        
        if (this.autoConnectValue && this.hubUrlValue && this.topicValue) {
            this.startListening()
        }
        
        // Gérer la visibilité de la page
        document.addEventListener('visibilitychange', this.handleVisibilityChange.bind(this))
    }

    disconnect() {
        this.stopListening()
        document.removeEventListener('visibilitychange', this.handleVisibilityChange.bind(this))
    }

    startListening() {
        if (this.eventSource) {
            console.log('📡 Mercure déjà connecté')
            return
        }

        try {
            // Construire l'URL Mercure avec les topics
            const url = new URL(this.hubUrlValue)
            url.searchParams.append('topic', this.topicValue)
            
            if (this.jwtValue) {
                url.searchParams.append('authorization', `Bearer ${this.jwtValue}`)
            }

            // Créer la connexion EventSource
            this.eventSource = new EventSource(url.toString())
            
            this.eventSource.onopen = () => {
                console.log('✅ Connexion Mercure établie')
                this.updateStatus('🟢 Connecté')
                this.dispatch('connected')
            }

            this.eventSource.onmessage = (event) => {
                this.handleMessage(event)
            }

            this.eventSource.onerror = (error) => {
                console.error('❌ Erreur Mercure:', error)
                this.updateStatus('🔴 Erreur')
                this.dispatch('error', { detail: error })
                
                // Tentative de reconnexion après 5 secondes
                setTimeout(() => {
                    if (!this.eventSource || this.eventSource.readyState === EventSource.CLOSED) {
                        this.startListening()
                    }
                }, 5000)
            }

        } catch (error) {
            console.error('❌ Erreur lors de l\'initialisation de Mercure:', error)
            this.updateStatus('🔴 Erreur')
        }
    }

    stopListening() {
        if (this.eventSource) {
            this.eventSource.close()
            this.eventSource = null
            console.log('📡 Connexion Mercure fermée')
            this.updateStatus('🔴 Déconnecté')
            this.dispatch('disconnected')
        }
    }

    handleMessage(event) {
        try {
            const data = JSON.parse(event.data)
            console.log('📨 Message Mercure reçu:', data)
            
            this.updateStatus('🟢 Message reçu')
            this.addMessage(`[${new Date().toLocaleTimeString()}] ${data.type || 'Message'}: ${data.message || 'Données reçues'}`)
            
            // Dispatcher l'événement avec les données
            this.dispatch('messageReceived', { detail: data })
            
            // Gérer les différents types de messages
            switch (data.type) {
                case 'game.move':
                    this.handleGameMove(data)
                    break
                case 'game.status':
                    this.handleGameStatus(data)
                    break
                case 'game.player_joined':
                    this.handlePlayerJoined(data)
                    break
                case 'game.timer_update':
                    this.handleTimerUpdate(data)
                    break
                default:
                    this.handleGenericMessage(data)
            }
            
        } catch (error) {
            console.error('❌ Erreur lors du traitement du message Mercure:', error)
        }
    }

    handleGameMove(data) {
        // Déclencher l'actualisation de l'échiquier
        this.dispatch('gameMove', { detail: data })
        
        // Actualiser l'affichage des coups si disponible
        if (data.move && document.getElementById('moves-list')) {
            this.dispatch('movesUpdated', { detail: { moves: data.moves || [] } })
        }
    }

    handleGameStatus(data) {
        // Mettre à jour le statut de la partie
        this.dispatch('statusUpdated', { detail: data })
    }

    handlePlayerJoined(data) {
        // Gérer l'arrivée d'un nouveau joueur
        this.dispatch('playerJoined', { detail: data })
    }

    handleTimerUpdate(data) {
        // Mettre à jour le timer
        this.dispatch('timerUpdated', { detail: data })
    }

    handleGenericMessage(data) {
        // Gérer les messages génériques
        this.dispatch('genericMessage', { detail: data })
    }

    updateStatus(status) {
        if (this.hasStatusTarget) {
            this.statusTarget.textContent = status
        }
    }

    addMessage(message) {
        if (this.hasMessagesTarget) {
            const messageElement = document.createElement('div')
            messageElement.className = 'mercure-message neo-text-xs neo-text-muted'
            messageElement.textContent = message
            
            this.messagesTarget.appendChild(messageElement)
            
            // Limiter à 10 messages maximum
            while (this.messagesTarget.children.length > 10) {
                this.messagesTarget.removeChild(this.messagesTarget.firstChild)
            }
            
            // Auto-scroll
            this.messagesTarget.scrollTop = this.messagesTarget.scrollHeight
        }
    }

    handleVisibilityChange() {
        if (document.hidden) {
            console.log('📱 Page cachée, Mercure reste connecté en arrière-plan')
            // On garde Mercure connecté même si la page est cachée
            // car les mises à jour temps réel sont importantes
        } else {
            console.log('📱 Page visible, vérification de la connexion Mercure')
            if (!this.eventSource && this.autoConnectValue) {
                this.startListening()
            }
        }
    }

    // Actions manuelles
    reconnect() {
        console.log('🔄 Reconnexion Mercure forcée')
        this.stopListening()
        setTimeout(() => this.startListening(), 1000)
    }

    toggle() {
        if (this.eventSource) {
            this.stopListening()
        } else {
            this.startListening()
        }
    }

    // Méthode pour envoyer des messages via Mercure (si configuré côté serveur)
    async publish(topic, data) {
        if (!this.hubUrlValue) {
            console.warn('⚠️ Impossible de publier : aucune URL de hub configurée')
            return
        }

        try {
            const publishUrl = this.hubUrlValue.replace('/.well-known/mercure', '/.well-known/mercure')
            const response = await fetch(publishUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Authorization': `Bearer ${this.jwtValue}`
                },
                body: new URLSearchParams({
                    topic: topic,
                    data: JSON.stringify(data)
                })
            })

            if (response.ok) {
                console.log('✅ Message publié sur Mercure')
            } else {
                console.warn('⚠️ Erreur lors de la publication:', response.status)
            }
        } catch (error) {
            console.error('❌ Erreur lors de la publication Mercure:', error)
        }
    }
}
