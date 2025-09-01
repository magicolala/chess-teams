# 🔔 Système de Notifications Chess Teams

## Fonctionnalités implémentées

### 1. **Panneau de contrôle des notifications** 
- Interface utilisateur intégrée dans la page de jeu
- Visible uniquement pendant une partie live et pour les joueurs authentifiés
- Trois types de notifications configurables :
  - **Notifications de bureau** : Pop-ups du navigateur
  - **Sons de notification** : Bips audio synthétisés
  - **Flash du titre** : Clignotement du titre de la page

### 2. **Système de préférences persistantes**
- Sauvegarde des préférences dans `localStorage`
- État par défaut : sons et flash activés, notifications de bureau désactivées
- Synchronisation entre les contrôleurs JavaScript

### 3. **Gestion intelligente des permissions**
- Demande de permission utilisateur pour les notifications de bureau
- Désactivation automatique des options non supportées
- Interface adaptative selon les capacités du navigateur

## Architecture technique

### Contrôleurs JavaScript (Stimulus)

#### `notification-controls_controller.js`
- **Responsabilité** : Gestion des préférences utilisateur
- **Targets** : `desktopToggle`, `soundToggle`, `flashToggle`
- **Fonctions principales** :
  - Chargement/sauvegarde des préférences
  - Vérification du support des notifications
  - Notifications de test pour chaque type
  - Émission d'événements de changement de préférences

#### `game-poll_controller.js` (modifié)
- **Responsabilité** : Actualisation du jeu et notifications de tour
- **Nouvelles fonctions** :
  - `getNotificationPreferences()` : Lecture des préférences
  - Respect des préférences dans `sendTurnNotification()`
  - Désactivation du système automatique de demande de permission

### Interface utilisateur

#### Template Twig
```html
<!-- Panneau ajouté dans templates/game/show.html.twig -->
{% if game.status == 'live' and userMembership %}
    <div class="neo-card neo-mb-lg" data-controller="notification-controls">
        <!-- 3 sections avec toggles pour chaque type de notification -->
    </div>
{% endif %}
```

#### Styles CSS
- Composant `.neo-toggle` pour les boutons basculeurs
- Design cohérent avec le NeoChess Framework
- Animations fluides et responsive

## Fonctionnement des notifications

### 1. **Détection du changement de tour**
```javascript
// Dans game-poll_controller.js
checkForTurnChange(gameState) {
    // Compare l'état précédent vs actuel
    // Déclenche les notifications si c'est maintenant au tour du joueur
}
```

### 2. **Envoi conditionnel des notifications**
```javascript
sendTurnNotification(gameState) {
    const prefs = this.getNotificationPreferences()
    
    // Notification de bureau (si activée + permission accordée)
    if (prefs.desktop && Notification.permission === 'granted') { ... }
    
    // Son (si activé)
    if (prefs.sound) { this.playNotificationSound() }
    
    // Flash du titre (si activé)  
    if (prefs.flash) { this.flashTitle(...) }
}
```

### 3. **Types de notifications**

#### **Notifications de bureau**
- Titre : "♟️ Chess Teams - C'est votre tour !"
- Corps : Information sur la partie
- Icône : Favicon du site
- Durée : Auto-fermeture après 8 secondes
- Cliquable : Ramène la fenêtre au premier plan

#### **Sons de notification**
- Oscillateur audio synthétique (800Hz, sinusoïdale)
- Durée : 500ms avec fade-out
- Volume modéré (0.3)
- Gestion des erreurs audio

#### **Flash du titre**
- Alternance entre titre original et "🔥 C'est votre tour !"
- 10 flashs maximum (5 cycles complets)
- Intervalle : 500ms
- Arrêt automatique si l'utilisateur revient sur la page

## Préférences utilisateur

### Clés localStorage
```javascript
'chess-notifications-desktop' // 'true'|'false' (défaut: false)
'chess-notifications-sound'   // 'true'|'false' (défaut: true) 
'chess-notifications-flash'   // 'true'|'false' (défaut: true)
```

### Événements personnalisés
```javascript
// Émis lors du changement de préférences
window.dispatchEvent(new CustomEvent('chess:notification-settings-changed', {
    detail: { desktop: true, sound: false, flash: true }
}))
```

## Tests et démonstration

### Fonctions de test intégrées
Chaque préférence dispose d'une fonction de test :

- **`showTestNotification()`** : Notification "🔔 Notifications activées"
- **`playTestSound()`** : Bip de test sur activation
- **`showTestFlash()`** : Flash de test "⚡ Test Flash ⚡"

### Activation des notifications de bureau
1. Utilisateur active le toggle "Notifications de bureau"
2. Si permission non accordée → Demande automatique
3. Si accordée → Notification de test + sauvegarde de la préférence
4. Si refusée → Désactivation du toggle + message d'erreur

## Intégration avec l'existant

### Compatibilité
- Aucun impact sur le système de polling existant
- Préservation des notifications flash intégrées à l'interface
- Contrôleurs découplés et indépendants

### Conditionnement d'affichage
- Panneau visible uniquement si :
  - `game.status == 'live'` 
  - `userMembership` existe (utilisateur authentifié et dans une équipe)

### Gestion d'erreurs
- Vérification du support des notifications
- Gestion des erreurs audio
- Désactivation gracieuse des fonctionnalités non supportées

## Améliorations futures possibles

1. **Personnalisation avancée**
   - Choix du son de notification
   - Durée configurable du flash
   - Types de notifications de bureau personnalisés

2. **Préférences serveur**
   - Sauvegarde des préférences en base de données
   - Synchronisation entre appareils

3. **Notifications push**
   - Service Worker pour les notifications hors ligne
   - Notifications même quand le navigateur est fermé

4. **Statistiques**
   - Suivi de l'utilisation des notifications
   - Optimisation des réglages par défaut

---

## 🚀 Installation et utilisation

1. Les fichiers ont été ajoutés/modifiés :
   - `assets/controllers/notification-controls_controller.js` (nouveau)
   - `assets/controllers/game-poll_controller.js` (modifié)
   - `templates/game/show.html.twig` (modifié)

2. Assets compilés avec `php bin/console asset-map:compile`

3. Utilisation immédiate :
   - Se connecter à une partie live
   - Le panneau "Notifications" apparaît dans la sidebar
   - Configurer les préférences selon les besoins
   - Les notifications se déclenchent automatiquement à chaque tour

**Le système est maintenant opérationnel ! 🎯**
