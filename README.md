# Chess-Teams ♟️

![Chess Teams Logo](https://img.shields.io/badge/Chess-Teams-blue?style=for-the-badge&logo=chess&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-7-green?style=for-the-badge&logo=symfony)
![PHP](https://img.shields.io/badge/PHP-8.4+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![License](https://img.shields.io/badge/License-Proprietary-red?style=for-the-badge)
[![CI](https://github.com/magicolala/chess-teams/actions/workflows/ci.yml/badge.svg)](https://github.com/magicolala/chess-teams/actions/workflows/ci.yml)
[![Code Style](https://github.com/magicolala/chess-teams/actions/workflows/code-style.yml/badge.svg)](https://github.com/magicolala/chess-teams/actions/workflows/code-style.yml)

**Chess-Teams** est une application web moderne pour jouer aux échecs en équipe. Deux équipes (A et B) se constituent et chaque membre joue **son propre coup** lorsque c'est à sa rotation : le serveur impose l'ordre de passage et refuse les coups qui ne proviennent pas du bon joueur. L'application fournit un échiquier interactif, un suivi complet de la partie et toutes les règles nécessaires pour terminer une rencontre en ligne.

## ⚡ Getting started (60s)

```bash
# Cloner & installer
git clone https://github.com/magicolala/chess-teams.git && cd chess-teams
composer install && cp .env .env.local

# Lancer l'infra et l'app
docker compose up -d database mercure php nginx

# Init DB + assets
docker compose exec php php bin/console doctrine:database:create
docker compose exec php php bin/console doctrine:migrations:migrate -n
docker compose exec php php bin/console asset-map:compile

# Ouvrir l'app
start http://localhost:8000
```

## Table des matières

- [✨ Fonctionnalités Principales](#-fonctionnalités-principales)
- [🛠️ Stack Technique](#️-stack-technique)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [🚀 Démarrage Rapide](#-démarrage-rapide)
- [🧪 Tests](#-tests)
- [📚 Architecture](#-architecture)
- [🎮 Comment Jouer](#-comment-jouer)
- [🛠️ Configuration](#️-configuration)
- [🔧 Développement](#-développement)
- [🔍 API](#-api)
- [🐛 Contribuer](#-contribuer)
- [📝 Changelog](#-changelog)
- [📞 Support](#-support)
- [📋 Licence](#-licence)
- [📗 Guide Agent](#-guide-agent)

## ✨ Fonctionnalités Principales

### 🎮 Gameplay

- **Jeu d'échecs en rotation** : chaque équipe dispose d'un ordre de passage ; un seul joueur peut jouer à la fois et un coup venant du mauvais joueur est rejeté par `GameMoveService`.
- **Échiquier interactif** : Neo Chess Board est utilisé côté front pour afficher et manipuler la position en direct (`assets/controllers/game-board_controller.js`).
- **Validation des coups** : le moteur (`ChessEngineInterface`) vérifie la légalité de chaque coup avant de l'enregistrer (`GameMoveService::applyMoveWithEngine`).
- **Règles complètes** : roque, prise en passant et promotions sont gérés par le moteur (`GameMoveService::applyMoveWithEngine`).
- **Modes de jeu** : mode classique ou variante "Werewolf" avec attribution de rôles secrets lors du lancement (`GameLifecycleService`).

### ⏱️ Système de Temps

- **Deadline par tour** : une échéance est associée à l'équipe qui doit jouer, avec rappel visuel côté front (`game-board_controller.js::tickTimer`).
- **Gestion des dépassements** : un service dédié enregistre un coup de type *timeout* et suspend la partie jusqu'à décision de l'équipe adverse (`GameTimeoutService`).
- **Mode rapide** : possibilité d'activer un chrono court partagé (endpoint `/games/{id}/enable-fast-mode`).

### 🎨 Interface Utilisateur

- **Design Neo-moderne** avec thème "midnight" appliqué aux pages Twig (`templates/home/index.html.twig`).
- **Pièces géométriques personnalisées** rendues par Neo Chess Board.
- **Responsive design** : grille CSS et composants adaptatifs (`templates/game/show.html.twig`).
- **Feedback visuel** : états "urgent" du timer et superpositions de verrouillage côté front (`game-board_controller.js`).

### 📊 Fonctionnalités Avancées

- **Historique complet** des coups avec notation SAN/UCI via `/games/{id}/moves`.
- **Export PGN** prêt à l'emploi (`PgnExporter` et route `/games/{id}/pgn`).
- **Système d'équipes persistant** : positions, statut prêt et rotation sont stockés en base (`Team`, `TeamMember`).
- **États de jeu** : `lobby`, `waiting`, `live`, `finished`/`done` et suivi des résultats (`Game::STATUS_*`).
- **Export FEN** et sauvegarde de la position après chaque coup (`Game::setFen`).

## 🛠️ Stack Technique

### Backend

- **Framework** : Symfony 7.3
- **Langage** : PHP 8.4+
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
- **Documentation** : Markdown avec diagrammes (voir `docs/php84-upgrade.md` pour la migration PHP 8.4)

## Prérequis

- PHP 8.4 ou supérieur
- Composer
- Docker Desktop (recommandé pour Postgres + Mercure)
- Symfony CLI (optionnel si vous ne lancez pas Nginx via Docker)
- Node.js et npm (optionnel — AssetMapper fonctionne sans bundler)

## Installation

1) Cloner le dépôt

```bash
git clone https://github.com/magicolala/chess-teams.git
cd chess-teams
```

2) Installer les dépendances PHP

```bash
composer install
```

3) Configurer l’environnement

```bash
cp .env .env.local
# Ouvrez .env.local et adaptez DATABASE_URL si besoin
```

4) Lancer l’infra (Docker) et initialiser la base

```bash
docker compose up -d database mercure
docker compose up -d php nginx

# Dans le conteneur php
docker compose exec php php bin/console doctrine:database:create
docker compose exec php php bin/console doctrine:migrations:migrate -n
```

5) Compiler les assets (AssetMapper)

```bash
docker compose exec php php bin/console asset-map:compile
```

Alternative locale (sans Docker) — si PHP et DB sont installés localement:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate -n
php bin/console asset-map:compile
symfony server:start -d
```

## 🚀 Démarrage Rapide

```bash
# 1) Cloner
git clone https://github.com/magicolala/chess-teams.git && cd chess-teams

# 2) Dépendances
composer install && cp .env .env.local

# 3) Infra & App
docker compose up -d database mercure php nginx

# 4) Base + Assets
docker compose exec php php bin/console doctrine:database:create
docker compose exec php php bin/console doctrine:migrations:migrate -n
docker compose exec php php bin/console asset-map:compile

# Accès: http://localhost:8000
```

## 🧪 Tests

### Exécuter les tests

```bash
# Tests complets
docker compose exec php ./vendor/bin/phpunit

# Tests avec couverture
docker compose exec php ./vendor/bin/phpunit --coverage-html coverage/

# Tests spécifiques
docker compose exec php ./vendor/bin/phpunit tests/Unit/
docker compose exec php ./vendor/bin/phpunit tests/Controller/
```

### Qualité du Code

```bash
# Vérifier le style de code (via scripts composer.json)
composer cs:check

# Corriger automatiquement
composer cs:fix

# Depuis Docker
docker compose exec php composer cs:check
docker compose exec php composer cs:fix
```

## 📚 Architecture

```
src/
├── Application/          # Services/DTO/Ports applicatifs
├── Command/              # Commandes (CLI)
├── Controller/           # Contrôleurs HTTP (ex: GameController.php)
├── Entity/               # Entités Doctrine
└── ...

assets/
├── controllers/          # Stimulus Controllers (ex: game-board_controller.js)
└── styles/               # Feuilles de style (app.css, neo-*.css)

templates/
├── base.html.twig        # Layout de base
└── game/                 # Vues de jeu
```

## 🎮 Comment Jouer

### Créer une Partie

1. Connectez-vous à l'application puis rendez-vous sur l'accueil.
2. Renseignez la durée de tour, la visibilité et (facultativement) le mode Werewolf, puis validez le formulaire.
3. Partagez le lien `/app/games?code=XXXX` ou le code d'invitation affiché pour permettre aux autres joueurs de vous rejoindre.

### Organiser les équipes

1. Chaque joueur choisit l'équipe A ou B ; sa position dans la liste définit son ordre de passage (`TeamMember::getPosition`).
2. Utilisez le bouton **Prêt** pour signaler que vous êtes disponible ; la partie ne peut démarrer que si tous les joueurs actifs sont prêts (`MarkPlayerReadyHandler`).
3. L'hôte lance la partie une fois les deux équipes constituées (`GameLifecycleService::start`).

### Interface de l'Échiquier

- 🔄 **Glisser-déposer** : Neo Chess Board permet de déplacer les pièces à la souris ou au doigt.
- ⏱️ **Minuteur** : le contrôleur Stimulus `game-board` affiche le temps restant et souligne les dernières secondes.
- 📜 **Historique** : la liste des coups est chargée à la volée via `/games/{id}/moves` et inclut la notation SAN/UCI.
- 🔐 **Contrôle du tour** : si ce n'est pas votre tour, l'échiquier reste en lecture seule et tout coup est refusé côté serveur (`GameMoveService::buildTurnContext`).
- ⚠️ **Validation** : coups illégaux ou hors rotation renvoient une erreur claire grâce au moteur et aux exceptions HTTP.

### Déroulement d'une partie

1. Quand la partie est en statut `live`, seul le joueur attendu peut interagir avec l'échiquier ; les autres voient une superposition de verrouillage.
2. Après chaque coup, l'ordre de passage avance automatiquement (`Team::setCurrentIndex`) et la main passe à l'équipe adverse.
3. En cas de dépassement du temps, un coup de type *timeout* est enregistré et l'équipe adverse doit décider de continuer ou de conclure la partie (`GameTimeoutService` et endpoint `/games/{id}/timeout-decision`).
4. Les joueurs peuvent exporter la partie au format PGN via `/games/{id}/pgn`.

## 🛠️ Configuration

### Variables d'Environnement

```bash
# Base de données (si Docker: host = database)
DATABASE_URL=postgresql://app:!ChangeMe!@database:5432/app

# Mercure
MERCURE_URL=https://localhost/.well-known/mercure
MERCURE_JWT_SECRET=!ChangeThisMercureHubJWTSecretKey!

# Environnement
APP_ENV=dev
APP_SECRET=your-secret-key

# CORS (exemple)
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
# Gestion de partie
POST   /games                     # Créer une partie (authentifié)
POST   /games/join/{code}         # Rejoindre une partie via un code
POST   /games/{id}/start          # Lancer la partie (créateur uniquement)
POST   /games/{id}/ready          # Marquer un joueur prêt ou non
POST   /games/{id}/move           # Jouer un coup (rotation et légalité vérifiées)
POST   /games/{id}/tick           # Vérifier/appliquer un timeout sur le tour courant
POST   /games/{id}/timeout-decision  # Décider du sort d'une équipe après timeout
POST   /games/{id}/enable-fast-mode  # Activer le chrono rapide (1 minute)
POST   /games/{id}/claim-victory     # Revendiquer la victoire après timeouts successifs

# Consultation
GET    /games/{id}                # Détails de la partie (statut, équipes, deadline)
GET    /games/{id}/moves          # Historique des coups (filtrable avec ?since={ply})
GET    /games/{id}/state          # État complet pour l'auto-refresh (ETag + moves)
GET    /games/{id}/pgn            # Export PGN de la partie
```

### Format des Réponses

```json
{
  "id": "0bf6d5cb-...",
  "status": "live",
  "fen": "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1",
  "ply": 4,
  "turnTeam": "A",
  "turnDeadline": 1700000000000,
  "teams": {
    "A": { "currentIndex": 1, "members": [{ "userId": "...", "displayName": "Alice", "position": 0, "ready": true }] },
    "B": { "currentIndex": 0, "members": [{ "userId": "...", "displayName": "Bob", "position": 0, "ready": true }] }
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

## 📗 Guide Agent

Pour les contributeurs et agents IA: voir `AGENT_GUIDE.md` pour un guide détaillé (stack, workflows, bonnes pratiques, dépannage).
