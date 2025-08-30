# 🔌 Documentation API - Chess-Teams

Cette documentation décrit l'API REST de Chess-Teams pour l'intégration et le développement d'applications tierces.

## 📚 Table des Matières

1. [Vue d'ensemble](#vue-densemble)
2. [Authentification](#authentification)
3. [Format des Réponses](#format-des-réponses)
4. [Gestion des Erreurs](#gestion-des-erreurs)
5. [Endpoints de Jeu](#endpoints-de-jeu)
6. [Endpoints Utilisateur](#endpoints-utilisateur)
7. [WebSocket Events](#websocket-events)
8. [Exemples d'Utilisation](#exemples-dutilisation)
9. [SDK et Clients](#sdk-et-clients)

## 🎯 Vue d'ensemble

### Base URL
```
Production: https://chess-teams.com/api
Development: http://localhost:8000/api
```

### Versioning
L'API suit le versioning sémantique. La version actuelle est `v1`.

### Content-Type
Toutes les requêtes et réponses utilisent le format JSON :
```
Content-Type: application/json
Accept: application/json
```

## 🔐 Authentification

Chess-Teams utilise une authentification par session avec support optionnel des tokens JWT.

### Session Authentication
```http
POST /api/login
Content-Type: application/json

{
  "username": "player@chess-teams.com",
  "password": "secure_password"
}
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": "uuid-here",
      "username": "player",
      "email": "player@chess-teams.com",
      "roles": ["ROLE_USER"]
    },
    "session_id": "sess_123456"
  }
}
```

### Headers d'authentification
```http
Cookie: PHPSESSID=sess_123456
# ou
Authorization: Bearer jwt-token-here
```

## 📋 Format des Réponses

### Structure Standard
Toutes les réponses API suivent cette structure :

```json
{
  "success": boolean,
  "data": object | array | null,
  "meta": {
    "timestamp": "2025-01-15T10:30:00Z",
    "version": "1.0.0",
    "request_id": "req_123456"
  },
  "errors": [
    {
      "code": "ERROR_CODE",
      "message": "Description de l'erreur",
      "field": "nom_du_champ" // optionnel
    }
  ]
}
```

### Codes de Statut HTTP
- **200** : Succès
- **201** : Créé avec succès
- **400** : Requête invalide
- **401** : Non authentifié
- **403** : Accès interdit
- **404** : Ressource non trouvée
- **409** : Conflit (ex: coup illégal)
- **422** : Données invalides
- **500** : Erreur serveur

## ❌ Gestion des Erreurs

### Format des Erreurs
```json
{
  "success": false,
  "data": null,
  "errors": [
    {
      "code": "INVALID_MOVE",
      "message": "Le coup e2e5 est illégal dans cette position",
      "field": "move"
    }
  ]
}
```

### Codes d'Erreur Courants
- `INVALID_CREDENTIALS` : Identifiants incorrects
- `GAME_NOT_FOUND` : Partie inexistante
- `INVALID_MOVE` : Coup d'échecs illégal
- `NOT_YOUR_TURN` : Ce n'est pas votre tour
- `GAME_FINISHED` : La partie est terminée
- `INSUFFICIENT_PERMISSIONS` : Permissions insuffisantes

## 🎮 Endpoints de Jeu

### Obtenir les Détails d'une Partie

```http
GET /api/games/{gameId}
```

**Paramètres :**
- `gameId` (string) : ID unique de la partie

**Réponse :**
```json
{
  "success": true,
  "data": {
    "game": {
      "id": "game_123",
      "status": "live",
      "fen": "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1",
      "turnTeam": "WHITE",
      "turnDeadline": 1640995200000,
      "whiteTeam": {
        "id": "team_white",
        "players": [
          {
            "id": "player_1",
            "username": "AliceChess"
          }
        ]
      },
      "blackTeam": {
        "id": "team_black",
        "players": [
          {
            "id": "player_2",
            "username": "BobGambit"
          }
        ]
      },
      "createdAt": "2025-01-15T10:00:00Z",
      "lastMove": {
        "from": "e2",
        "to": "e4",
        "san": "e4",
        "uci": "e2e4"
      }
    }
  }
}
```

### Jouer un Coup

```http
POST /api/games/{gameId}/move
Content-Type: application/json

{
  "move": "e2e4",
  "promotion": "q"  // optionnel pour promotion des pions
}
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "move": {
      "from": "e2",
      "to": "e4",
      "piece": "p",
      "san": "e4",
      "uci": "e2e4",
      "fen": "rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq e3 0 1"
    },
    "gameStatus": "live"
  }
}
```

### Obtenir l'Historique des Coups

```http
GET /api/games/{gameId}/moves
```

**Paramètres de requête :**
- `page` (int) : Page des résultats (défaut: 1)
- `limit` (int) : Nombre de coups par page (défaut: 50)

**Réponse :**
```json
{
  "success": true,
  "data": {
    "moves": [
      {
        "id": "move_1",
        "ply": 1,
        "from": "e2",
        "to": "e4",
        "piece": "p",
        "san": "e4",
        "uci": "e2e4",
        "team": "WHITE",
        "playedAt": "2025-01-15T10:05:00Z",
        "player": {
          "id": "player_1",
          "username": "AliceChess"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "total_moves": 10,
      "per_page": 50
    }
  }
}
```

### Créer une Nouvelle Partie

```http
POST /api/games
Content-Type: application/json

{
  "timeControl": "10+5",  // 10 minutes + 5 secondes d'incrément
  "rated": false,
  "variant": "standard",
  "teamSize": 2,
  "private": false
}
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "game": {
      "id": "game_new_123",
      "status": "waiting",
      "timeControl": "10+5",
      "fen": "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1",
      "whiteTeam": {
        "id": "team_white_new",
        "players": [
          {
            "id": "player_1",
            "username": "AliceChess"
          }
        ]
      },
      "joinCode": "CHESS123"  // pour parties privées
    }
  }
}
```

### Rejoindre une Partie

```http
POST /api/games/{gameId}/join
Content-Type: application/json

{
  "team": "black",  // "white" ou "black"
  "joinCode": "CHESS123"  // optionnel pour parties privées
}
```

### Abandonner une Partie

```http
POST /api/games/{gameId}/resign
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "game": {
      "status": "finished",
      "result": "BLACK_WINS",
      "reason": "resignation"
    }
  }
}
```

### Proposer Match Nul

```http
POST /api/games/{gameId}/draw-offer
```

### Accepter/Refuser Match Nul

```http
POST /api/games/{gameId}/draw-response
Content-Type: application/json

{
  "accept": true
}
```

## 👥 Endpoints Utilisateur

### Obtenir le Profil Utilisateur

```http
GET /api/users/{userId}
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": "player_1",
      "username": "AliceChess",
      "email": "alice@chess-teams.com",
      "rating": 1500,
      "gamesPlayed": 125,
      "wins": 68,
      "losses": 45,
      "draws": 12,
      "joinedAt": "2024-01-01T00:00:00Z",
      "lastActiveAt": "2025-01-15T10:30:00Z"
    }
  }
}
```

### Obtenir l'Historique des Parties

```http
GET /api/users/{userId}/games
```

**Paramètres de requête :**
- `status` (string) : live, finished, abandoned
- `page` (int) : Page des résultats
- `limit` (int) : Nombre de parties par page

### Rechercher des Utilisateurs

```http
GET /api/users/search
```

**Paramètres de requête :**
- `q` (string) : Terme de recherche
- `limit` (int) : Nombre de résultats (max: 20)

## 🔌 WebSocket Events

Chess-Teams utilise WebSocket pour les mises à jour en temps réel.

### Connexion
```javascript
const socket = new WebSocket('ws://localhost:8000/ws');
```

### Events Entrants (du serveur vers le client)

#### Coup Joué
```json
{
  "type": "move_played",
  "data": {
    "gameId": "game_123",
    "move": {
      "from": "e2",
      "to": "e4",
      "san": "e4"
    },
    "fen": "nouvelle_position_fen",
    "turnTeam": "BLACK"
  }
}
```

#### Partie Terminée
```json
{
  "type": "game_finished",
  "data": {
    "gameId": "game_123",
    "result": "WHITE_WINS",
    "reason": "checkmate"
  }
}
```

#### Mise à Jour du Timer
```json
{
  "type": "timer_update",
  "data": {
    "gameId": "game_123",
    "whiteTime": 580000,  // en millisecondes
    "blackTime": 600000,
    "turnDeadline": 1640995200000
  }
}
```

### Events Sortants (du client vers le serveur)

#### S'abonner aux mises à jour d'une partie
```json
{
  "type": "subscribe_game",
  "data": {
    "gameId": "game_123"
  }
}
```

## 💡 Exemples d'Utilisation

### JavaScript/Node.js

```javascript
class ChessTeamsAPI {
  constructor(baseURL = 'http://localhost:8000/api') {
    this.baseURL = baseURL;
    this.sessionId = null;
  }

  async login(username, password) {
    const response = await fetch(`${this.baseURL}/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, password })
    });
    
    const data = await response.json();
    if (data.success) {
      this.sessionId = data.data.session_id;
    }
    return data;
  }

  async getGame(gameId) {
    const response = await fetch(`${this.baseURL}/games/${gameId}`, {
      headers: { 'Cookie': `PHPSESSID=${this.sessionId}` }
    });
    return await response.json();
  }

  async makeMove(gameId, move) {
    const response = await fetch(`${this.baseURL}/games/${gameId}/move`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Cookie': `PHPSESSID=${this.sessionId}`
      },
      body: JSON.stringify({ move })
    });
    return await response.json();
  }
}

// Usage
const api = new ChessTeamsAPI();
await api.login('alice@chess.com', 'password');
const game = await api.getGame('game_123');
const result = await api.makeMove('game_123', 'e2e4');
```

### Python

```python
import requests
import json

class ChessTeamsAPI:
    def __init__(self, base_url="http://localhost:8000/api"):
        self.base_url = base_url
        self.session = requests.Session()
    
    def login(self, username, password):
        response = self.session.post(f"{self.base_url}/login", 
            json={"username": username, "password": password})
        return response.json()
    
    def get_game(self, game_id):
        response = self.session.get(f"{self.base_url}/games/{game_id}")
        return response.json()
    
    def make_move(self, game_id, move):
        response = self.session.post(f"{self.base_url}/games/{game_id}/move",
            json={"move": move})
        return response.json()

# Usage
api = ChessTeamsAPI()
api.login("alice@chess.com", "password")
game = api.get_game("game_123")
result = api.make_move("game_123", "e2e4")
```

### cURL

```bash
# Authentification
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"alice@chess.com","password":"password"}' \
  -c cookies.txt

# Obtenir une partie
curl -X GET http://localhost:8000/api/games/game_123 \
  -b cookies.txt

# Jouer un coup
curl -X POST http://localhost:8000/api/games/game_123/move \
  -H "Content-Type: application/json" \
  -d '{"move":"e2e4"}' \
  -b cookies.txt
```

## 🛠️ SDK et Clients

### JavaScript SDK
```bash
npm install chess-teams-js-sdk
```

```javascript
import ChessTeams from 'chess-teams-js-sdk';

const client = new ChessTeams({
  apiUrl: 'http://localhost:8000/api',
  websocketUrl: 'ws://localhost:8000/ws'
});

await client.auth.login('username', 'password');
const game = await client.games.get('game_123');
```

### PHP SDK
```bash
composer require chess-teams/php-sdk
```

```php
use ChessTeams\Client;

$client = new Client('http://localhost:8000/api');
$client->auth()->login('username', 'password');
$game = $client->games()->get('game_123');
```

## 📊 Rate Limiting

L'API applique des limitations de taux pour éviter les abus :

- **Authentifié** : 1000 requêtes/heure
- **Non-authentifié** : 100 requêtes/heure
- **Coups d'échecs** : 1 coup/seconde maximum

Headers de réponse :
```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1640995200
```

## 🔒 Sécurité

### CORS
Les requêtes cross-origin sont autorisées pour :
- Domaines configurés dans `CORS_ALLOW_ORIGIN`
- Localhost en développement

### CSRF Protection
Les requêtes POST/PUT/DELETE nécessitent un token CSRF :
```http
X-CSRF-Token: token_here
```

### Input Validation
Tous les inputs sont validés côté serveur :
- Coups d'échecs via le moteur Chess.js
- Données utilisateur via les contraintes Symfony
- Protection contre les injections SQL

## 📈 Performance

### Cache
- Positions d'échecs mises en cache (Redis)
- Réponses API mises en cache selon le contexte
- Headers de cache HTTP appropriés

### Pagination
Toutes les listes sont paginées :
```json
{
  "data": [...],
  "pagination": {
    "current_page": 1,
    "total_pages": 10,
    "total_items": 500,
    "per_page": 50
  }
}
```

## 🆘 Support

- **Documentation** : [https://docs.chess-teams.com](https://docs.chess-teams.com)
- **Issues** : [GitHub Issues](https://github.com/magicolala/chess-teams/issues)
- **Discord** : [Serveur Développeurs](https://discord.gg/chess-teams)
- **Email** : api-support@chess-teams.com

---

Cette documentation est maintenue automatiquement. Dernière mise à jour : 2025-01-15
