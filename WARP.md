# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

Chess-Teams is a real-time team-based chess application built with Symfony 6.4. Players collaborate in teams (White/Black) to make chess moves together, featuring an interactive chessboard, real-time updates, and turn timers.

## Technology Stack

- **Backend**: PHP 8.4+, Symfony 6.4, Doctrine ORM
- **Frontend**: JavaScript, Stimulus, AssetMapper (no Webpack)
- **Database**: PostgreSQL (production), SQLite (testing)
- **Real-time**: Symfony Mercure for real-time updates
- **Chess Engine**: chesslablab/php-chess via P-Chess wrapper
- **Testing**: PHPUnit 9.5

## Architecture

This project follows **Hexagonal Architecture** (Clean Architecture) with clear separation of concerns:

### Core Layers

- **Domain**: Pure business entities and repository interfaces (`src/Domain/`)
- **Application**: Use cases, DTOs, and services (`src/Application/`)  
- **Infrastructure**: External adapters for database, chess engine (`src/Infrastructure/`)
- **Controllers**: HTTP entry points (`src/Controller/`)

### Key Components

- **Game Entity**: Manages chess game state (FEN, status, teams, timers)
- **Chess Engine Port**: Abstraction for chess move validation and FEN manipulation
- **Use Case Handlers**: Business logic for game operations (create, join, move, timeout)
- **Repository Pattern**: Domain-driven data access interfaces

### Game Flow

1. Create game → Generate invite code → Teams join → Start game
2. Players make moves via UCI notation → Engine validates → State updates
3. Turn timers with timeout handling → Real-time updates via Mercure

## Common Development Commands

### Setup and Installation

```bash
# Install PHP dependencies
composer install

# Set up database
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Install frontend dependencies (ImportMap handles JS dependencies)
php bin/console importmap:install

# Start development server
symfony server:start -d

# Start Docker services (PostgreSQL + Mercure)
docker compose up -d
```

### Development Workflow

```bash
# Run tests
./vendor/bin/phpunit
# Or via Docker
docker compose exec php ./vendor/bin/phpunit

# Run single test
./vendor/bin/phpunit tests/RegistrationControllerTest.php

# Code style check
composer cs:check
# Or via Docker
docker compose exec php composer cs:check

# Fix code style
composer cs:fix
# Or via Docker
docker compose exec php composer cs:fix

# Clear cache
php bin/console cache:clear
# Or via Docker
docker compose exec php php bin/console cache:clear

# Generate migration after entity changes
php bin/console doctrine:migrations:diff
# Or via Docker
docker compose exec php php bin/console doctrine:migrations:diff

# Debug routes
php bin/console debug:router
# Or via Docker
docker compose exec php php bin/console debug:router

# Debug services
php bin/console debug:container
# Or via Docker
docker compose exec php php bin/console debug:container
```

### Database Commands

```bash
# Create test users (custom command)
php bin/console app:create-test-users

# Reset database schema
php bin/console doctrine:schema:drop --force
php bin/console doctrine:schema:create
php bin/console doctrine:migrations:migrate
```

## Environment Configuration

The application uses environment-specific configuration:

- `.env` - Default configuration
- `.env.local` - Local overrides (not committed)
- `.env.test` - Test environment overrides

Key environment variables:

- `DATABASE_URL` - Database connection string
- `MERCURE_URL` - Mercure hub URL for real-time features
- `APP_ENV` - Environment mode (dev/prod/test)
 - `MERCURE_JWT_SECRET` - Secret key for Mercure (see compose.yaml)

## Testing Strategy

- **Functional Tests**: WebTestCase for HTTP endpoints
- **Unit Tests**: Isolated testing of use cases and services
- **Test Database**: Uses SQLite in-memory for speed
- **Test Setup**: Database is cleaned before each test

Example test run:

```bash
# Run all tests
./vendor/bin/phpunit

# Run with coverage (requires Xdebug)
./vendor/bin/phpunit --coverage-html coverage/
```

## Code Quality

- **PHP-CS-Fixer**: Enforces Symfony coding standards + PHP 8.4 features
- **PHPStan**: Static analysis (configure in composer.json if needed)
- **Doctrine**: Schema validation via migrations

## Real-time Features

The application uses **Symfony Mercure** for real-time updates:

- Game state changes broadcast to all connected clients
- Turn timer updates
- Move notifications
- Game status changes (started, finished)

## Frontend Integration

- **AssetMapper**: Modern asset management without Webpack
- **ImportMap**: Handles JavaScript dependencies (chess.js, chessboardjs, jQuery)
- **Stimulus**: JavaScript framework for progressive enhancement
- **Turbo**: For SPA-like navigation

## Key Files to Understand

### Core Business Logic

- `src/Entity/Game.php` - Central game state management
- `src/Application/UseCase/` - Business operations handlers
- `src/Infrastructure/Chess/PChessEngine.php` - Chess move validation

### API Endpoints

- `src/Controller/GameController.php` - RESTful game API
- `config/routes.yaml` - Route definitions

### Configuration

- `config/services.yaml` - Dependency injection setup
- `compose.yaml` - Docker services (PostgreSQL, Mercure)
- `importmap.php` - Frontend dependency mapping

---

For operational guides and Docker-based workflows, see `README.md` (Installation & Quickstart). For Windows/PowerShell commands, best practices, and troubleshooting, see `AGENT_GUIDE.md`.

When working with this codebase, prioritize understanding the game flow through the use case handlers and how the hexagonal architecture maintains clean separation between business logic and external concerns.
