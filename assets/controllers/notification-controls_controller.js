import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["desktopToggle", "soundToggle", "flashToggle"]
    
    connect() {
        console.log("🔔 NotificationControls controller connected")
        
        // Charger les préférences sauvegardées depuis localStorage
        this.loadPreferences()
        
        // Vérifier si les notifications sont supportées
        this.checkNotificationSupport()
    }
    
    loadPreferences() {
        // Charger les préférences depuis localStorage
        const desktopEnabled = localStorage.getItem('chess-notifications-desktop') === 'true'
        const soundEnabled = localStorage.getItem('chess-notifications-sound') !== 'false' // activé par défaut
        const flashEnabled = localStorage.getItem('chess-notifications-flash') !== 'false' // activé par défaut
        
        // Appliquer les préférences aux toggles
        this.desktopToggleTarget.checked = desktopEnabled
        this.soundToggleTarget.checked = soundEnabled
        this.flashToggleTarget.checked = flashEnabled
        
        console.log("💾 Préférences chargées:", { desktopEnabled, soundEnabled, flashEnabled })
    }
    
    checkNotificationSupport() {
        if (!("Notification" in window)) {
            console.warn("🚫 Les notifications de bureau ne sont pas supportées")
            this.desktopToggleTarget.disabled = true
            this.desktopToggleTarget.parentElement.style.opacity = '0.5'
            
            // Ajouter un message d'information
            const infoDiv = this.element.querySelector('.neo-text-xs')
            if (infoDiv) {
                infoDiv.innerHTML = `
                    <i class="material-icons tiny">warning</i>
                    Les notifications de bureau ne sont pas supportées par ce navigateur
                `
            }
        } else if (Notification.permission === 'denied') {
            console.warn("🚫 Les notifications de bureau sont bloquées")
            this.desktopToggleTarget.disabled = true
            this.desktopToggleTarget.parentElement.style.opacity = '0.5'
        }
    }
    
    toggleDesktopNotifications(event) {
        const enabled = event.target.checked
        
        if (enabled && "Notification" in window) {
            // Demander la permission si pas encore accordée
            if (Notification.permission === 'default') {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        this.saveDesktopPreference(true)
                        console.log("✅ Notifications de bureau activées")
                        
                        // Montrer une notification de test
                        this.showTestNotification()
                    } else {
                        // Permission refusée, désactiver le toggle
                        event.target.checked = false
                        this.saveDesktopPreference(false)
                        console.log("❌ Permission de notification refusée")
                    }
                })
            } else if (Notification.permission === 'granted') {
                this.saveDesktopPreference(true)
                console.log("✅ Notifications de bureau activées")
                this.showTestNotification()
            } else {
                // Permission déjà refusée
                event.target.checked = false
                this.saveDesktopPreference(false)
                console.log("❌ Notifications bloquées")
            }
        } else {
            this.saveDesktopPreference(false)
            console.log("🔕 Notifications de bureau désactivées")
        }
    }
    
    toggleSoundNotifications(event) {
        const enabled = event.target.checked
        this.saveSoundPreference(enabled)
        
        if (enabled) {
            console.log("🔊 Sons de notification activés")
            // Jouer un son de test
            this.playTestSound()
        } else {
            console.log("🔇 Sons de notification désactivés")
        }
    }
    
    toggleFlashNotifications(event) {
        const enabled = event.target.checked
        this.saveFlashPreference(enabled)
        
        if (enabled) {
            console.log("⚡ Flash du titre activé")
            // Montrer un flash de test
            this.showTestFlash()
        } else {
            console.log("🌑 Flash du titre désactivé")
        }
    }
    
    saveDesktopPreference(enabled) {
        localStorage.setItem('chess-notifications-desktop', enabled.toString())
        // Déclencher un événement pour informer les autres contrôleurs
        window.dispatchEvent(new CustomEvent('chess:notification-settings-changed', {
            detail: { desktop: enabled }
        }))
    }
    
    saveSoundPreference(enabled) {
        localStorage.setItem('chess-notifications-sound', enabled.toString())
        window.dispatchEvent(new CustomEvent('chess:notification-settings-changed', {
            detail: { sound: enabled }
        }))
    }
    
    saveFlashPreference(enabled) {
        localStorage.setItem('chess-notifications-flash', enabled.toString())
        window.dispatchEvent(new CustomEvent('chess:notification-settings-changed', {
            detail: { flash: enabled }
        }))
    }
    
    showTestNotification() {
        if (Notification.permission === 'granted') {
            const notification = new Notification('🔔 Notifications activées', {
                body: 'Vous recevrez maintenant des notifications quand c\'est votre tour de jouer !',
                icon: '/favicon.ico',
                tag: 'chess-test-notification'
            })
            
            // Auto-fermer après 3 secondes
            setTimeout(() => {
                notification.close()
            }, 3000)
            
            // Ramener la fenêtre au premier plan si on clique sur la notification
            notification.onclick = () => {
                window.focus()
                notification.close()
            }
        }
    }
    
    playTestSound() {
        // Créer un contexte audio simple pour jouer un bip
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)()
            const oscillator = audioContext.createOscillator()
            const gainNode = audioContext.createGain()
            
            oscillator.connect(gainNode)
            gainNode.connect(audioContext.destination)
            
            oscillator.frequency.setValueAtTime(800, audioContext.currentTime) // Fréquence 800Hz
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime) // Volume modéré
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1) // Fade out
            
            oscillator.start(audioContext.currentTime)
            oscillator.stop(audioContext.currentTime + 0.1) // Durée de 100ms
        } catch (error) {
            console.warn("🔇 Impossible de jouer le son de test:", error)
        }
    }
    
    showTestFlash() {
        const originalTitle = document.title
        let flashCount = 0
        
        const flashInterval = setInterval(() => {
            document.title = flashCount % 2 === 0 ? '⚡ Test Flash ⚡' : originalTitle
            flashCount++
            
            if (flashCount >= 6) { // 3 flashs complets
                clearInterval(flashInterval)
                document.title = originalTitle
            }
        }, 500)
    }
}
