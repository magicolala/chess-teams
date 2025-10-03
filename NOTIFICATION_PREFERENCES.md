# Menu des Préférences de Notifications - Chess Teams

## 📋 Vue d'ensemble

Cette fonctionnalité ajoute un menu des préférences de notifications accessible via le profil utilisateur dans l'interface Chess Teams. Le menu est intégré dans le design NeoChess existant et utilise Symfony 7 avec Stimulus pour les interactions.

## 🎯 Fonctionnalités

### Menu Dropdown Utilisateur
- **Accès rapide** : Clic sur le profil utilisateur dans le header
- **Mini-contrôles** : Toggles compacts pour les préférences principales directement dans le dropdown
- **Navigation** : Liens vers le profil complet et les préférences détaillées

### Préférences de Notifications
- **Notifications Bureau** : Notifications système du navigateur
- **Sons de Notification** : Signaux audio pour les événements
- **Flash du Titre** : Clignotement du titre de la page
- **Sauvegarde Locale** : Préférences stockées dans le localStorage du navigateur

### Interface Utilisateur
- **Design Cohérent** : Intégré au thème NeoChess existant
- **Responsive** : Adapté aux appareils mobiles
- **Animations** : Transitions fluides et feedback visuel
- **Accessibilité** : Support clavier et navigation intuitive

## 📁 Structure des Fichiers

### Contrôleurs Symfony
```
src/Controller/
├── UserProfileController.php      # Gestion du profil et préférences
```

### Templates Twig
```
templates/
├── base.html.twig                 # Header modifié avec dropdown
└── user_profile/
    ├── profile.html.twig          # Page de profil principale
    └── notifications.html.twig    # Page des préférences détaillées
```

### Contrôleurs Stimulus (JavaScript)
```
assets/controllers/
├── user-menu_controller.js        # Gestion du dropdown utilisateur
└── notification-controls_controller.js  # Contrôles des préférences (existant)
```

### Styles CSS
```
assets/styles/
├── neo-components.css             # Styles étendus pour le menu utilisateur
├── neo-utilities.css              # Classes utilitaires
└── neo-chess-framework.css        # Framework de base (existant)
```

## 🔧 Routes Disponibles

| Route | URL | Description |
|-------|-----|-------------|
| `app_user_profile` | `/profile` | Page principale du profil |
| `app_user_notifications` | `/profile/notifications` | Préférences de notifications détaillées |

## 💡 Utilisation

### Accès au Menu
1. Cliquer sur le profil utilisateur dans le header
2. Le dropdown s'ouvre avec :
   - Informations utilisateur
   - Liens de navigation
   - Préférences rapides (toggles miniatures)

### Configuration des Préférences
1. **Dans le Dropdown** : Utiliser les mini-toggles pour un accès rapide
2. **Page Dédiée** : Accéder à `/profile/notifications` pour une interface complète

### Sauvegarde
- Les préférences sont automatiquement sauvegardées dans le localStorage
- Chaque modification déclenche un événement `chess:notification-settings-changed`

## 🎨 Design System

### Classes CSS Principales
- `.neo-user-dropdown` : Container du menu dropdown
- `.neo-user-dropdown-content` : Contenu du menu
- `.neo-preference-item` : Élément de préférence
- `.neo-toggle` : Switch de préférence

### Variables CSS Utilisées
```css
--neo-bg-card          /* Arrière-plan des cartes */
--neo-bg-hover         /* Arrière-plan au survol */
--neo-text-primary     /* Texte principal */
--neo-text-muted       /* Texte secondaire */
--neo-accent-primary   /* Couleur d'accent */
--neo-border-light     /* Bordures légères */
```

## ⚡ Interactions JavaScript

### Contrôleur UserMenu
```javascript
// Ouverture/fermeture du menu
toggle()

// Fermeture automatique
handleOutsideClick()
handleEscapeKey()

// Événements personnalisés
dispatch('opened')
dispatch('closed')
```

### Contrôleur NotificationControls
```javascript
// Gestion des préférences
toggleDesktopNotifications()
toggleSoundNotifications()
toggleFlashNotifications()

// Tests de fonctionnalités
showTestNotification()
playTestSound()
showTestFlash()
```

## 📱 Responsive Design

### Points de Rupture
- **Mobile** (`<768px`) : Menu full-width, toggles verticaux
- **Desktop** (`>=768px`) : Menu positionné à droite, layout horizontal

### Adaptations Mobiles
- Informations utilisateur masquées dans le header
- Menu dropdown adapté à la largeur de l'écran
- Contrôles de préférences empilés verticalement

## 🔐 Sécurité

- **Authentification** : Routes protégées par `IS_AUTHENTICATED_FULLY`
- **Validation** : Contrôles côté serveur pour l'accès aux données
- **Données Locales** : Préférences stockées localement, pas de données sensibles

## 🧪 Tests de Fonctionnalités

### Notifications Bureau
- Vérification du support navigateur
- Demande d'autorisation automatique
- Notification de test à l'activation

### Sons de Notification
- Génération de sons via Web Audio API
- Son de test à l'activation
- Gestion des erreurs audio

### Flash du Titre
- Animation du titre de la page
- Cycle de clignotement contrôlé
- Restauration du titre original

## 📈 Améliorations Futures

### Fonctionnalités Suggérées
- **Préférences par Type** : Notifications différenciées par événement
- **Personnalisation** : Sons personnalisés, durées ajustables
- **Synchronisation** : Sauvegarde côté serveur optionnelle
- **Groupes de Notifications** : Gestion par catégorie

### Optimisations Techniques
- **Service Worker** : Notifications même quand l'onglet n'est pas actif
- **Push Notifications** : Notifications serveur via WebPush
- **Analytics** : Tracking d'utilisation des préférences

## 🐛 Dépannage

### Problèmes Courants
- **Menu ne s'ouvre pas** : Vérifier que Stimulus est chargé
- **Préférences non sauvées** : Contrôler le localStorage du navigateur
- **Styles cassés** : Vérifier l'ordre de chargement des CSS

### Logs de Debug
```javascript
// Console logs automatiques
console.log("👤 UserMenu controller connected")
console.log("🔔 NotificationControls controller connected")
console.log("✅ Notifications de bureau activées")
```

## 🤝 Contribution

Pour contribuer à cette fonctionnalité :
1. Respecter le design system NeoChess
2. Maintenir la compatibilité avec les contrôleurs Stimulus existants
3. Tester sur mobile et desktop
4. Documenter les nouveaux composants CSS

---

**Version** : 1.0.0  
**Framework** : Symfony 7 + Stimulus
**Design** : NeoChess Framework  
**Compatibilité** : Navigateurs modernes (ES6+)
