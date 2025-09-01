import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["menu"]
    static classes = ["active"]
    
    connect() {
        console.log("👤 UserMenu controller connected")
        
        // Fermer le menu si on clique en dehors
        document.addEventListener('click', this.handleOutsideClick.bind(this))
        
        // Fermer le menu avec la touche Escape
        document.addEventListener('keydown', this.handleEscapeKey.bind(this))
    }
    
    disconnect() {
        // Nettoyer les event listeners
        document.removeEventListener('click', this.handleOutsideClick.bind(this))
        document.removeEventListener('keydown', this.handleEscapeKey.bind(this))
    }
    
    toggle(event) {
        event.stopPropagation()
        
        const isActive = this.element.classList.contains('neo-active')
        
        if (isActive) {
            this.close()
        } else {
            this.open()
        }
    }
    
    open() {
        // Fermer tous les autres dropdowns ouverts
        document.querySelectorAll('.neo-user-dropdown.neo-active').forEach(dropdown => {
            if (dropdown !== this.element) {
                dropdown.classList.remove('neo-active')
            }
        })
        
        this.element.classList.add('neo-active')
        
        // Animation de l'icône
        const expandIcon = this.element.querySelector('.material-icons:last-child')
        if (expandIcon) {
            expandIcon.style.transform = 'rotate(180deg)'
        }
        
        console.log("📖 Menu utilisateur ouvert")
        
        // Déclencher un événement personnalisé
        this.dispatch('opened', { detail: { menu: this.menuTarget } })
    }
    
    close() {
        this.element.classList.remove('neo-active')
        
        // Animation de l'icône
        const expandIcon = this.element.querySelector('.material-icons:last-child')
        if (expandIcon) {
            expandIcon.style.transform = 'rotate(0deg)'
        }
        
        console.log("📕 Menu utilisateur fermé")
        
        // Déclencher un événement personnalisé
        this.dispatch('closed', { detail: { menu: this.menuTarget } })
    }
    
    handleOutsideClick(event) {
        // Ne rien faire si le menu n'est pas ouvert
        if (!this.element.classList.contains('neo-active')) return
        
        // Ne rien faire si le clic est dans le menu
        if (this.element.contains(event.target)) return
        
        // Fermer le menu
        this.close()
    }
    
    handleEscapeKey(event) {
        if (event.key === 'Escape' && this.element.classList.contains('neo-active')) {
            this.close()
        }
    }
    
    // Action pour les liens du menu (optionnel)
    navigate(event) {
        // Fermer le menu quand on clique sur un lien
        setTimeout(() => this.close(), 100)
    }
    
    // Animation de hover pour les items
    itemHover(event) {
        const item = event.currentTarget
        item.style.transform = 'translateX(2px)'
    }
    
    itemLeave(event) {
        const item = event.currentTarget
        item.style.transform = 'translateX(0)'
    }
}
