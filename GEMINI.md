# Guide Agent IA — Chess-Teams

Ce document explique la codebase, les conventions de code, l’architecture et l’environnement d’exécution afin d’aider une IA en mode Agent à fournir des réponses et des actions précises sur ce projet.

## Table des matières

- [1) Contexte & Objectif](#1-contexte--objectif)
- [2) Stack Technique](#2-stack-technique)
- [3) Architecture & Structure](#3-architecture--structure)
- [4) Conventions de Code](#4-conventions-de-code)
- [5) Exécution Locale (Windows + PowerShell)](#5-exécution-locale-windows--powershell)
  - [5.1) Variables d’environnement clés](#51-variables-denvironnement-clés)
- [6) Commandes Utiles](#6-commandes-utiles-powershell)
  - [6.1) Rappels — Exécuter dans le conteneur php](#61-rappels--exécuter-dans-le-conteneur-php)
  - [6.2) Raccourcis via scripts Composer](#62-raccourcis-via-scripts-composer)
- [7) Bonnes Pratiques pour une IA Agent](#7-bonnes-pratiques-pour-une-ia-agent)
- [8) Points d’attention spécifiques au projet](#8-points-dattention-spécifiques-au-projet)
- [9) Workflow recommandé (IA Agent)](#9-workflow-recommandé-ia-agent)
- [10) Annexe — Services Docker](#10-annexe--services-docker-composeyaml)
- [11) Workflow Git & CI](#11-workflow-git--ci)
- [12) Check-list PR](#12-check-list-pr)
- [13) Dépannage](#13-dépannage)

## 1) Contexte & Objectif

- Application web d’échecs collaboratifs (jeu en équipe) nommée `chess-teams`.
- Objectif de ce guide: permettre à une IA Agent de comprendre rapidement le stack, la structure du dépôt et les conventions, pour proposer des changements, des diagnostics et des commandes adaptées à mon environnement.
- Poste de dev: Windows PC, terminal par défaut PowerShell.
- Dev local: Symfony 7 (PHP 8.4+), avec Docker pour les services d’infra (PostgreSQL, Mercure), et AssetMapper pour les assets.

## 2) Stack Technique

- Backend: Symfony 7, PHP >= 8.4.
- ORM & DB: Doctrine ORM 3.x, Migrations, PostgreSQL (via Docker).
- Messaging/Realtime: Mercure (via Docker) + Symfony UX Turbo.
- Cache/Queue (potentiel): Redis (Predis lib incluse, usage optionnel).
- Moteur d’échecs: `chesslablab/php-chess`.
- Frontend: Stimulus (Hotwired) + Symfony AssetMapper. Styles CSS modernes.
- Qualité/Tests: PHPUnit, PHP-CS-Fixer (scripts Composer disponibles).

Références projet:

- `composer.json` (contraintes, scripts `cs:check` et `cs:fix`).
- `compose.yaml` (services `database` Postgres et `mercure`).
- `assets/` (contrôleurs Stimulus et styles).
- `templates/` (Twig).
- `src/` (contrôleurs, domaines, services, etc.).
- `docs/php84-upgrade.md` — Mémo de migration et checklist PHP 8.4.

## 3) Architecture & Structure

- Dossier principal du code: `src/`
  - `src/Controller/` — Contrôleurs HTTP (ex: `GameWebController.php`).
  - `src/Application/` — DTO, ports, services applicatifs.
  - `src/Infrastructure/` — Implémentations techniques (ex: Doctrine Repositories).
  - `src/Entity/` — Entités Doctrine (selon besoin).
- Vues: `templates/` (Twig, pages HTML côté serveur).
- Front: `assets/`
  - `assets/controllers/` — Stimulus (`game-board_controller.js`, etc.).
  - `assets/styles/` — CSS (`app.css`, `neo-chess-framework.css`, etc.).
- Config: `config/` (packages, routes, sécurité, etc.).
- Public: `public/` (entrée HTTP, CSS compilé par AssetMapper, etc.).
- Tests: `tests/` (PHPUnit – unitaires/fonctionnels).
- Migrations: `migrations/` (Doctrine Migrations).

Arborescence (extrait):

```text
.
├─ assets/
│  ├─ controllers/
│  └─ styles/
├─ bin/
├─ config/
│  ├─ packages/
│  └─ routes/
├─ docker/
│  ├─ nginx/
│  └─ php/
├─ migrations/
├─ public/
├─ src/
│  ├─ Application/
│  ├─ Command/
│  ├─ Controller/
│  └─ ...
├─ templates/
├─ tests/
└─ .github/workflows/
```

## 4) Conventions de Code

- Standard: PSR-12.
- Outils:
  - Vérification style: `composer cs:check`.
  - Correction auto: `composer cs:fix`.
- Commits: Conventional Commits (`feat:`, `fix:`, `refactor:`, `test:`, `docs:`, etc.).
- PHP: typage strict recommandé, privilégier les Value Objects et les Services pour la logique métier.
- Tests: viser une couverture solide, tests unitaires/fonctionnels sur les cas critiques du jeu (validation coups, états de partie, timer).

## 5) Exécution Locale (Windows + PowerShell)

Pré-requis locaux:

- PHP 8.4+, Composer, Symfony CLI.
- Docker Desktop (Windows) pour services DB et Mercure.

Étapes fréquentes:

1. Installation des dépendances PHP

```powershell
composer install
```

2. Variables d’environnement

```powershell
Copy-Item .env .env.local -ErrorAction SilentlyContinue
# Ouvrir .env.local et adapter DATABASE_URL si nécessaire
```

3. Lancer les services d’infra (Docker)

Le fichier `compose.yaml` fournit:

- `database`: Postgres 16 (alpine)
- `mercure`: hub Mercure (dev mode activé par défaut)

```powershell
docker compose up -d database mercure
# Pour arrêter
# docker compose down
```

4. Initialiser la base de données (dans le conteneur php)

```powershell
docker compose exec php php bin/console doctrine:database:create
docker compose exec php php bin/console doctrine:migrations:migrate -n
```

5. Démarrer l’application

- Option Nginx (recommandée, configurée par compose.override.yaml)

```powershell
docker compose up -d php nginx
# Accès: http://localhost:8000
```

- Option Symfony CLI (si vous ne voulez pas du conteneur nginx)

```powershell
symfony server:start -d
# Accès: https://127.0.0.1:8000
```

6. Compiler/actualiser les assets (AssetMapper)

```powershell
docker compose exec php php bin/console asset-map:compile
```

Alternative locale (sans Docker):

```powershell
# Si PHP est installé en local et que l'app tourne sans Docker pour PHP
php bin\console asset-map:compile
```

Note: Le projet contient aussi un `package-lock.json`. AssetMapper peut fonctionner sans bundler (Webpack/Vite). Les scripts npm sont optionnels; si vous utilisez npm pour des utilitaires, installez avec `npm install` puis exécutez vos scripts si nécessaire.

### 5.1) Variables d’environnement clés

Définissez/ajustez ces variables dans `.env.local` (ou variables d’environnement système) en dev:

- `DATABASE_URL` — Connexion PostgreSQL (via le service `database` Docker).
- `MERCURE_URL` — URL publique du hub (ex: `https://localhost/.well-known/mercure`).
- `MERCURE_JWT_SECRET` — Clé JWT Mercure (voir `compose.yaml`, à changer en prod).
- `APP_ENV` — `dev` par défaut en local.
- `APP_SECRET` — Secret Symfony (générer via `bin/console secrets:generate-keys` en prod si nécessaire).

## 6) Commandes Utiles (PowerShell)

- Qualité du code

```powershell
docker compose exec php composer cs:check
docker compose exec php composer cs:fix
```

- Tests PHPUnit

```powershell
# Tous les tests
docker compose exec php ./vendor/bin/phpunit

# Avec couverture
docker compose exec php ./vendor/bin/phpunit --coverage-html coverage/

# Cibler un répertoire
docker compose exec php ./vendor/bin/phpunit tests/Controller
```

- Base de données

```powershell
docker compose exec php php bin/console doctrine:migrations:migrate -n
docker compose exec php php bin/console doctrine:schema:validate
```

- Mercure (santé)

```powershell
# Vérifier le healthcheck Mercure (le container expose HTTPS interne)
# Vous pouvez aussi vérifier les logs:
docker compose logs -f mercure
```

## 6.1) Rappels — Exécuter dans le conteneur php

Pour toutes les commandes Symfony/Doctrine/Composer, utilisez systématiquement le conteneur `php`:

```powershell
docker compose exec php php bin/console <commande>
docker compose exec php composer <commande>
docker compose exec php ./vendor/bin/phpunit <options>
```

Exemples courants:

```powershell
# Migrations
docker compose exec php php bin/console doctrine:migrations:migrate -n

# Validation du schéma
docker compose exec php php bin/console doctrine:schema:validate

# Compiler les assets (AssetMapper)
docker compose exec php php bin/console asset-map:compile
```

## 6.2) Raccourcis via scripts Composer

Vous pouvez définir/consulter des scripts dans `composer.json` et les invoquer ainsi:

```powershell
docker compose exec php composer <script>
```

Exemples typiques (si présents dans `composer.json`):

- `composer cs:check` — Vérifie le style via PHP-CS-Fixer en mode dry-run.
- `composer cs:fix` — Corrige automatiquement le style.
- `composer test` — Lance la suite PHPUnit.

## 7) Bonnes Pratiques pour une IA Agent

- Préserver la sécurité et l’idempotence
  - Proposer d’abord des commandes de lecture/diagnostic avant des actions destructrices.
  - Demander confirmation avant: opérations DB (drop, reset), modifications massives, installation système.

- Adapter aux chemins et au shell Windows
  - Utiliser des chemins `\` quand nécessaire pour PHP/Composer/veneurs.
  - Préférer des exemples PowerShell.

- Respecter la structure et les conventions
  - Créer/modifier le code dans les répertoires appropriés (ex: contrôleurs dans `src/Controller/`, services dans `src/Application/Service/`).
  - Ajouter/mettre à jour les tests associés dans `tests/`.
  - Exécuter `composer cs:check` et, si pertinent, `composer cs:fix` avant de proposer une PR.

- Symfony/Doctrine
  - Générer les migrations pour toute modification d’entités (`doctrine:migrations:diff`), puis `migrate`.
  - Ne pas commiter des migrations locales instables sans validation.

- Frontend/Assets
  - Utiliser AssetMapper (`asset-map:compile`) pour les changements de ressources.
  - Les Stimulus controllers vivent dans `assets/controllers/` et sont référencés dans `assets/bootstrap.js`.

- Observabilité & Debug
  - Profiler: `/ _profiler` en dev (si bundle activé).
  - Logs: `var/log/*.log`.

## 8) Points d’attention spécifiques au projet

- Jeu collaboratif: garder la logique de validation des coups cohérente entre client (hints) et serveur (vérité). Le serveur doit être source d’autorité.
- Minuteur de tour: les endpoints côté serveur doivent rester atomiques et résistants aux rafraîchissements/latences.
- Événements en temps réel: Mercure est utilisé pour pousser les mises à jour. Veiller aux CORS et aux clés JWT dans un `.env.local` sécurisé.
- Dépendances PHP: `lcobucci/jwt` reste verrouillé en 5.3.0 via un dépôt Composer interne qui élargit la contrainte PHP à 8.4.
  Les `composer update` classiques sont possibles tant que ce dépôt est conservé (cf. `docs/php84-upgrade.md`).

## 9) Workflow recommandé (IA Agent)

1. Lire le contexte (fichiers concernés, `composer.json`, `compose.yaml`).
2. Définir un plan court, annoncer les actions (création/édition de fichier, commande à exécuter).
3. Appliquer des changements minimaux, testables et réversibles.
4. Lancer les tests/unitaires et vérifications style.
5. Résumer les modifications et fournir les commandes de run/test correspondantes.

## 10) Annexe — Services Docker (compose.yaml)

- `database` — Postgres 16 alpine
  - Variables par défaut: `POSTGRES_DB=app`, `POSTGRES_USER=app`, `POSTGRES_PASSWORD=!ChangeMe!`
  - Volume: `database_data`
- `mercure` — Hub Mercure (dev mode via Caddy)
  - JWT publisher/subscriber: `!ChangeThisMercureHubJWTSecretKey!` (à changer en prod)
  - CORS par défaut: `http://127.0.0.1:8000`
  - Volumes: `mercure_data`, `mercure_config`

## 11) Workflow Git & CI

- Branches:
  - `main` — branche stable.
  - Feature branches nommées `feat/<courte-description>`; correctifs `fix/<courte-description>`; refactors `refactor/<...>`.
- Commit message: respecter Conventional Commits.
- CI:
  - `/.github/workflows/ci.yml` — lance tests et vérifs essentielles.
  - `/.github/workflows/code-style.yml` — vérifie/corrige le style.
  - Viser un pipeline vert avant merge.

## 12) Check-list PR

- Description claire du problème/feature et de la solution.
- Tests ajoutés/ajustés et verts localement.
- `composer cs:check` sans erreurs (ou `cs:fix` appliqué).
- Migrations générées et incluses si des entités/DB changent.
- Documentation mise à jour (README, ce guide, commentaires de code si utile).

## 13) Dépannage

- Base inaccessible depuis l’app:
  - Vérifier `docker compose ps` et les logs `docker compose logs -f database`.
  - Confirmer `DATABASE_URL` dans `.env.local` (host = nom du service `database`).
- Erreurs Mercure (CORS/JWT):
  - Valider `MERCURE_URL`, `CORS_ALLOWED_ORIGINS` et `MERCURE_JWT_SECRET` (voir `compose.yaml`).
  - Naviguer sur le hub ou jeter un œil aux logs `docker compose logs -f mercure`.
- Problèmes d’assets:
  - Recompiler: `docker compose exec php php bin/console asset-map:compile`.
  - Vider le cache Symfony: `docker compose exec php php bin/console cache:clear`.
- Erreurs Doctrine/Migrations:
  - `doctrine:schema:validate` pour détecter les décalages.
  - En dev, regénérer une migration propre si nécessaire.

Si vous êtes une IA Agent: suivez ce guide pour contextualiser vos réponses, générer des PRs propres et proposer des commandes PowerShell/Docker adaptées à Windows. Merci !
