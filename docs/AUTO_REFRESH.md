# 🔄 Système d'Actualisation Automatique

Ce projet dispose de plusieurs systèmes d'actualisation automatique pour maintenir l'interface utilisateur synchronisée en temps réel.

## 🎯 Contrôleurs Disponibles

### 1. `game-poll` - Polling Spécialisé pour les Parties
**Utilisation :** Actualisation spécifique aux parties d'échecs
**Fréquence :** 2 secondes par défaut
**Endpoint :** `/games/{id}/state`

```html
<div data-controller="game-poll" 
     data-game-poll-game-id-value="{{ game.id }}"
     data-game-poll-poll-interval-value="2000">
    <!-- Contenu de la partie -->
</div>
```

**Fonctionnalités :**
- ✅ Actualisation des coups
- ✅ Mise à jour du statut de la partie
- ✅ Gestion du timer
- ✅ Mise à jour de l'échiquier
- ✅ Surlignage du joueur actuel
- ✅ Pause automatique quand la page n'est pas visible

### 2. `auto-refresh` - Actualisation Générale
**Utilisation :** Pour n'importe quel contenu
**Fréquence :** 5 secondes par défaut
**Endpoint :** Configurable ou page actuelle

```html
<div data-controller="auto-refresh" 
     data-auto-refresh-interval-value="5000"
     data-auto-refresh-url-value="/path/to/refresh"
     data-auto-refresh-target-value="#specific-element">
    <!-- Contenu à actualiser -->
</div>
```

**Fonctionnalités :**
- ✅ Actualisation de la page entière ou d'éléments spécifiques
- ✅ Support Turbo pour les performances
- ✅ Contrôles utilisateur (pause, intervalle)
- ✅ Gestion de la visibilité de la page

### 3. `turbo-refresh` - Turbo Streams
**Utilisation :** Pour des mises à jour partielles efficaces
**Fréquence :** 3 secondes par défaut
**Endpoint :** URL retournant des Turbo Streams

```html
<div data-controller="turbo-refresh" 
     data-turbo-refresh-stream-url-value="/games/{{ game.id }}/stream"
     data-turbo-refresh-interval-value="3000">
    <!-- Contenu géré par Turbo Streams -->
</div>
```

**Fonctionnalités :**
- ✅ Mises à jour partielles ultra-rapides
- ✅ Actions : replace, update, append, prepend, remove
- ✅ Fallback manuel si Turbo n'est pas disponible

### 4. `mercure` - Communication Temps Réel
**Utilisation :** Push temps réel depuis le serveur
**Fréquence :** Instantané (événements)
**Endpoint :** Hub Mercure

```html
<div data-controller="mercure"
     data-mercure-hub-url-value="{{ mercure_hub_url }}"
     data-mercure-topic-value="game.{{ game.id }}"
     data-mercure-jwt-value="{{ mercure_jwt }}">
    
    <div data-mercure-target="status">🔴 Déconnecté</div>
    <div data-mercure-target="messages"></div>
</div>
```

**Fonctionnalités :**
- ✅ Communication bidirectionnelle
- ✅ Événements en temps réel
- ✅ Reconnexion automatique
- ✅ Gestion des topics multiples

## 📊 Comparaison des Méthodes

| Méthode | Latence | Bande passante | Complexité | Temps réel |
|---------|---------|----------------|------------|------------|
| **game-poll** | ~2s | Moyenne | Faible | ❌ |
| **auto-refresh** | ~5s | Élevée | Faible | ❌ |
| **turbo-refresh** | ~3s | Faible | Moyenne | ❌ |
| **mercure** | Instantané | Très faible | Élevée | ✅ |

## 🚀 Utilisation Recommandée

### Pour une Partie d'Échecs :
```html
<!-- Approche hybride recommandée -->
<div data-controller="game-poll mercure" 
     data-game-poll-game-id-value="{{ game.id }}"
     data-mercure-topic-value="game.{{ game.id }}"
     data-mercure-hub-url-value="{{ mercure_hub_url }}">
    
    <!-- Interface de la partie -->
</div>
```

### Pour une Liste Générale :
```html
<div data-controller="auto-refresh" 
     data-auto-refresh-interval-value="10000">
    <!-- Liste des parties, utilisateurs, etc. -->
</div>
```

### Pour des Mises à Jour Partielles :
```html
<div data-controller="turbo-refresh" 
     data-turbo-refresh-stream-url-value="/stream/endpoint">
    <!-- Contenu mis à jour par chunks -->
</div>
```

## 🎛️ Contrôles Utilisateur

Tous les contrôleurs supportent :
- **Pause/Reprendre** : `data-action="controller#togglePause"`
- **Actualisation forcée** : `data-action="controller#forceRefresh"`
- **Changement d'intervalle** : Sélecteur automatiquement ajouté

## 📡 Configuration Côté Serveur

### Endpoint d'État de Partie
```php
// GET /games/{id}/state
// Retourne l'état complet de la partie au format JSON
```

### Turbo Streams (optionnel)
```php
// Retourner du contenu Turbo Stream
return $this->render('game/turbo_update.stream.html.twig', [
    'game' => $game
]);
```

### Mercure (optionnel)
```php
// Publier des événements Mercure
$this->publisher->publish(new Update(
    'game.' . $game->getId(),
    json_encode(['type' => 'game.move', 'data' => $moveData])
));
```

## 🔧 Personnalisation

### Changer les Intervalles par Défaut
```javascript
// Dans vos templates
data-controller-interval-value="1000" // 1 seconde
```

### Événements Personnalisés
```javascript
// Écouter les événements
document.addEventListener('game-poll:game-updated', (event) => {
    console.log('Partie mise à jour:', event.detail)
})

document.addEventListener('mercure:message-received', (event) => {
    console.log('Message Mercure:', event.detail)
})
```

### Debug
Tous les contrôleurs loggent leurs activités dans la console. Ouvrez les DevTools pour suivre les actualisations.

## 💡 Conseils de Performance

1. **Utilisez Mercure** pour les mises à jour critiques temps réel
2. **Combinez polling + Mercure** pour la redondance
3. **Ajustez les intervalles** selon l'importance des données
4. **Utilisez Turbo Streams** pour les mises à jour partielles fréquentes
5. **Le polling s'arrête automatiquement** quand la page n'est pas visible

## 🐛 Dépannage

### Mercure ne fonctionne pas
- Vérifiez que le hub Mercure est démarré
- Vérifiez les variables d'environnement `MERCURE_URL` et `MERCURE_PUBLIC_URL`
- Vérifiez que le JWT est valide

### Polling trop lent
- Réduisez l'intervalle avec `data-controller-interval-value`
- Vérifiez que l'endpoint répond rapidement

### Turbo Streams ne s'appliquent pas
- Vérifiez que les éléments ont des IDs corrects
- Vérifiez le format des Turbo Streams côté serveur
