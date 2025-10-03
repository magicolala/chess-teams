# Chess-Teams ♟️

![Chess Teams Logo](https://img.shields.io/badge/Chess-Teams-blue?style=for-the-badge&logo=chess&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-6.4-green?style=for-the-badge&logo=symfony)
![PHP](https://img.shields.io/badge/PHP-8.4+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![License](https://img.shields.io/badge/License-Proprietary-red?style=for-the-badge)
[![CI](https://github.com/magicolala/chess-teams/actions/workflows/ci.yml/badge.svg)](https://github.com/magicolala/chess-teams/actions/workflows/ci.yml)
[![Code Style](https://github.com/magicolala/chess-teams/actions/workflows/code-style.yml/badge.svg)](https://github.com/magicolala/chess-teams/actions/workflows/code-style.yml)

**Chess-Teams** est une application web moderne et innovante pour jouer aux échecs en équipe. Elle permet à plusieurs joueurs de collaborer au sein de deux équipes (Blancs et Noirs) pour décider du meilleur coup à jouer collectivement. L'application offre une expérience de jeu immersive avec un échiquier interactif en temps réel, un système de notation avancé et une interface utilisateur élégante.

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

## 📗 Guide Agent

Pour les contributeurs et agents IA: voir `AGENT_GUIDE.md` pour un guide détaillé (stack, workflows, bonnes pratiques, dépannage).
