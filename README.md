# Chess-Teams ♟️

![Chess Teams Logo](https://img.shields.io/badge/Chess-Teams-blue?style=for-the-badge&logo=chess&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-6.4-green?style=for-the-badge&logo=symfony)
![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![License](https://img.shields.io/badge/License-Proprietary-red?style=for-the-badge)

**Chess-Teams** est une application web moderne et innovante pour jouer aux échecs en équipe. Elle permet à plusieurs joueurs de collaborer au sein de deux équipes (Blancs et Noirs) pour décider du meilleur coup à jouer collectivement. L'application offre une expérience de jeu immersive avec un échiquier interactif en temps réel, un système de notation avancé et une interface utilisateur élégante.

## ✨ Fonctionnalités Principales

### 🎮 Gameplay

- **Jeu d'échecs collaboratif** : Plusieurs joueurs par équipe peuvent discuter et décider des coups ensemble
- **Échiquier interactif** : Interface moderne avec pièces géométriques inspirées de Neo Chess Board
- **Validation des coups** : Moteur d'échecs intégré avec Chess.js pour la validation complète des règles
- **Support des coups spéciaux** : Roque, en passant, promotion automatique des pions

### ⏱️ Système de Temps

- **Minuteur par tour** avec indicateurs visuels d'urgence (< 30 secondes)
- **Gestion automatique** des timeouts et fins de partie
- **Affichage en temps réel** du temps restant

### 🎨 Interface Utilisateur

- **Design Neo-moderne** avec thème "midnight" élégant
- **Pièces géométriques personnalisées** (identiques au Neo Chess Board Ts Library)
- **Responsive design** adaptatif pour tous les écrans
- **Animations fluides** pour les mouvements de pièces

### 📊 Fonctionnalités Avancées

- **Historique complet** des coups avec notation SAN et UCI
- **Auto-scroll** vers le dernier coup joué
- **Système d'équipes** avec gestion des utilisateurs
- **États de jeu** : live, finished, paused
- **Export FEN** pour analyser les positions

## 🛠️ Stack Technique

### Backend

- **Framework** : Symfony 6.4 (LTS)
- **Langage** : PHP 8.1+
- **ORM** : Doctrine avec migrations
- **Base de données** : PostgreSQL/MySQL compatible
- **Moteur d'échecs** : chesslablab/php-chess
- **Cache** : Redis avec Predis

### Frontend

- **Framework JS** : Stimulus (Hotwired)
- **Build System** : Symfony AssetMapper
- **Échiquier** : Neo Chess Board (système géométrique custom)
- **Validation côté client** : Chess.js
- **Styles** : CSS moderne avec variables et grid

### DevOps & Qualité

- **Tests** : PHPUnit avec fixtures
- **Code Quality** : PHP-CS-Fixer avec règles strictes
- **CI/CD** : GitHub Actions ready
- **Documentation** : Markdown avec diagrammes

## Prérequis

- PHP 8.1 ou supérieur
- Composer
- Symfony CLI
- Node.js et npm (ou yarn)

## Installation

1. Clonez le dépôt :

    ```bash
    git clone https://github.com/votre-utilisateur/chess-teams.git
    cd chess-teams
    ```

2. Installez les dépendances PHP :

    ```bash
    composer install
    ```

3. Installez les dépendances frontend :

    ```bash
    npm install
    # ou
    yarn install
    ```

4. Compilez les assets frontend :

    ```bash
    npm run build
    # ou pour le développement avec surveillance des fichiers
    npm run watch
    ```

5. Configurez vos variables d'environnement. Copiez `.env` vers `.env.local` et personnalisez-le, notamment la `DATABASE_URL` :

    ```bash
    cp .env .env.local
    # ouvrez .env.local et modifiez DATABASE_URL
    ```

6. Initialisez la base de données :

    ```bash
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    ```

7. Démarrez le serveur web local :

    ```bash
    symfony server:start -d
    ```

    L'application devrait être accessible à l'adresse `https://127.0.0.1:8000`.

## 🚀 Démarrage Rapide

### Installation Express

```bash
# Cloner et installer
git clone https://github.com/magicolala/chess-teams.git
cd chess-teams/api
composer install

# Configurer la base de données
cp .env .env.local
# Éditez .env.local avec vos paramètres DB

# Initialiser
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Compiler les assets
php bin/console asset-map:compile

# Lancer le serveur
symfony server:start
# ou
php -S localhost:8000 -t public/
```

## 🧪 Tests

### Exécuter les tests

```bash
# Tests complets
./vendor/bin/phpunit

# Tests avec couverture
./vendor/bin/phpunit --coverage-html coverage/

# Tests spécifiques
./vendor/bin/phpunit tests/Unit/
./vendor/bin/phpunit tests/Functional/
```

### Qualité du Code

```bash
# Vérifier le style de code
composer cs:check

# Corriger automatiquement
composer cs:fix
```

## 📚 Architecture

```
src/
├── Controller/          # Contrôleurs HTTP
│   ├── GameController.php
│   └── GameWebController.php
├── Entity/             # Entités Doctrine
│   ├── Game.php
│   ├── Move.php
│   └── User.php
├── Repository/         # Repositories Doctrine
└── Service/            # Services métier

assets/
├── controllers/        # Stimulus Controllers
│   └── game-board_controller.js
└── styles/             # Feuilles de style
    ├── app.css
    ├── neo-chess-framework.css
    └── neo-components.css

templates/
├── base.html.twig      # Layout de base
├── game/               # Vues de jeu
└── security/           # Authentification
```

## 🎮 Comment Jouer

### Créer une Partie

1. Connectez-vous à l'application
2. Cliquez sur "Nouvelle Partie"
3. Invitez d'autres joueurs dans votre équipe
4. Attendez qu'une équipe adverse se forme

### Jouer en Équipe

1. **Discutez** avec votre équipe via le chat intégré
2. **Analysez** la position ensemble
3. **Proposé** et évaluez différents coups
4. **Jouez** le coup décidé collectivement

### Interface de l'Échiquier

- 🔄 **Glisser-déposer** : Cliquez et glissez les pièces
- ⏱️ **Minuteur** : Temps restant affiché en temps réel
- 📜 **Historique** : Liste des coups avec notation
- ⚠️ **Validation** : Coups illégaux rejetés automatiquement

## 🛠️ Configuration

### Variables d'Environnement

```bash
# Base de données
DATABASE_URL=postgresql://user:pass@localhost:5432/chess_teams

# Redis (optionnel)
REDIS_URL=redis://localhost:6379

# Environnement
APP_ENV=dev
APP_SECRET=your-secret-key

# Sécurité
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

### Configuration de Production

```bash
# Optimiser l'autoloader
composer dump-autoload --optimize

# Vider les caches
php bin/console cache:clear --env=prod

# Compiler les assets pour la production
php bin/console asset-map:compile --env=prod
```

## 🔧 Développement

### Ajouter une Nouvelle Fonctionnalité

1. Créez une branche feature : `git checkout -b feature/ma-fonctionnalite`
2. Développez votre fonctionnalité
3. Ajoutez des tests : `tests/Unit/` ou `tests/Functional/`
4. Vérifiez la qualité : `composer cs:check`
5. Soumettez une PR

### Debugging

```bash
# Activer le debug
export APP_ENV=dev

# Profiler Symfony
http://localhost:8000/_profiler

# Logs en temps réel
tail -f var/log/dev.log
```

### Hooks Git

```bash
# Pre-commit hook pour la qualité du code
echo '#!/bin/sh
composer cs:check' > .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

## 🔍 API

### Endpoints Principaux

```http
# Jeu
GET    /games/{id}           # Détails de la partie
POST   /games/{id}/move      # Jouer un coup
GET    /games/{id}/moves     # Historique des coups
POST   /games/{id}/tick      # Mise à jour du timer

# Utilisateurs
GET    /api/users            # Liste des utilisateurs
POST   /api/users            # Créer un utilisateur
```

### Format des Réponses

```json
{
  "success": true,
  "data": {
    "game": {
      "id": "uuid",
      "fen": "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1",
      "status": "live",
      "turnTeam": "WHITE",
      "turnDeadline": 1640995200000
    }
  }
}
```

## 🐛 Contribuer

### Étapes pour Contribuer

1. **Fork** le projet
2. **Clone** votre fork : `git clone https://github.com/your-username/chess-teams.git`
3. **Créez** une branche : `git checkout -b feature/amazing-feature`
4. **Committez** : `git commit -m 'feat: add amazing feature'`
5. **Poussez** : `git push origin feature/amazing-feature`
6. **Ouvrez** une Pull Request

### Conventions

- **Commits** : Format Conventional Commits (`feat:`, `fix:`, `docs:`)
- **Code Style** : PSR-12 avec règles PHP-CS-Fixer
- **Tests** : Couverture minimale de 80%
- **Documentation** : README à jour pour chaque fonctionnalité

## 📝 Changelog

### Version 2.0.0 (Actuelle)

- ✨ **Nouveau** : Pièces géométriques Neo Chess Board
- ✨ **Nouveau** : Thème midnight avec couleurs personnalisées
- 🔄 **Amélioration** : Système de sprites optimisé
- ⚖️ **Suppression** : Dépendances API Platform
- 🐛 **Correction** : Gestion améliorée du drag & drop

### Version 1.0.0

- ✨ Première version avec échiquier Chessground
- ✨ Système d'équipes collaboratives
- ✨ Interface utilisateur responsive

## 📞 Support

- **Issues** : [GitHub Issues](https://github.com/magicolala/chess-teams/issues)
- **Wiki** : [Documentation](https://github.com/magicolala/chess-teams/wiki)
- **Email** : <support@chess-teams.com>

## 📋 Licence

Ce projet est sous licence propriétaire. Tous droits réservés.

---

Développé avec ❤️ par l'équipe Chess-Teams

Made with Symfony 🎵 PHP ⚡ Neo Chess Board
