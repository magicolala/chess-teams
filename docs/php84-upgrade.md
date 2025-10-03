# Migration PHP 8.4

Ce document récapitule la migration du projet Chess-Teams vers PHP 8.4. Il sert de référence rapide pour les contributeurs et les agents IA amenés à travailler sur la stack.

## Résumé des changements

- **Runtime** : exigence PHP portée à 8.4+ (`composer.json`, Dockerfile, documentation utilisateur).
- **Outillage QA** : stabilisation de la stack de tests autour de PHPUnit 9.6 (compatible PHP 8.4) et harmonisation de `phpunit.xml.dist`/dépendances de dev.
- **Services applicatifs** : refactorisation des use cases `StartGame`, `MakeMove` et `TimeoutTick` vers de nouveaux services dédiés (`GameLifecycleService`, `GameMoveService`, `GameTimeoutService`) pour respecter SOLID.
- **Tests** : ajout de batteries de tests unitaires ciblant les nouveaux services et adaptation des tests de handlers.
- **Documentation** : harmonisation des guides (README, INSTALLATION, AGENT_GUIDE, GEMINI, WARP, CONTRIBUTING, CHANGELOG) et ajout de ce mémo.

## Détails techniques

### Dépendances

- `composer.json`
  - `"php": "^8.4"`.
  - `phpunit/phpunit` → `^9.6`.
  - `friendsofphp/php-cs-fixer` → `^3.86`.
  - `symfony/phpunit-bridge` → `^7.1` (compatible PHPUnit 9.6 / PHP 8.4).
- Regénérer `composer.lock` avec `composer update --lock` après toute mise à jour.

### Conteneurs & runtime

- `docker/php/Dockerfile` utilise désormais l’image de base `php:8.4-fpm-alpine`.
- Alias explicite `Symfony\Component\Lock\LockFactory` → `@lock.factory` dans `config/services.yaml` pour l’autowiring des nouveaux services.

### Qualité & tests

- `phpunit.xml.dist` aligné sur le schéma PHPUnit 9.6 (`https://schema.phpunit.de/9.6/phpunit.xsd`).
- Suppression du listener `SymfonyTestsListener` (obsolète depuis PHPUnit 9.5+) au profit du bootstrap personnalisé `tests/bootstrap.php`.
- Nouveaux tests :
  - `tests/Application/Service/Game/GameLifecycleServiceTest.php`
  - `tests/Application/Service/Game/GameMoveServiceTest.php`
  - `tests/Application/Service/Game/GameTimeoutServiceTest.php`
  - Adaptation des tests de handlers (`MakeMoveHandlerTest`, `TimeoutTickHandlerTest`).
- Les workflows GitHub Actions (`ci.yml`, `code-style.yml`) exécutent désormais PHP 8.4 pour rester cohérents avec la contrainte runtime.

### Refactorisation SOLID

- **GameLifecycleService** : encapsule la logique de démarrage de partie (validations, assignation des rôles loup-garou, mise à jour des deadlines) avec DTO `GameStartSummary`.
- **GameMoveService** : centralise les validations de coup, l’intégration moteur UCI, la persistance des `Move` et la gestion de fin de partie. Retourne un `MoveResult` pour les handlers.
- **GameTimeoutService** : gère le verrouillage, la création de coup de type timeout et l’état de décision à prendre par l’équipe adverse via `TimeoutResult`.
- Les handlers (`StartGameHandler`, `MakeMoveHandler`, `TimeoutTickHandler`) se concentrent désormais sur l’orchestration et la transformation vers les DTO de sortie.

## Checklist de validation post-migration

1. `composer install` avec PHP 8.4.
2. `composer run cs:check` (ou `cs:fix --dry-run`).
3. `composer run stan`.
4. `php bin/console about` pour vérifier l’environnement.
5. `./vendor/bin/phpunit` — tous les tests doivent passer.
6. Vérifier la génération d’assets : `php bin/console asset-map:compile`.

## Notes complémentaires

- Mettre à jour les environnements CI/CD (images Docker, runners) pour qu’ils fournissent PHP 8.4.
- Vérifier les extensions PHP optionnelles (Redis, APCu, etc.) sur chaque environnement.
- Pour les contributions futures, référencer cette page dans les guides Agents (`AGENT_GUIDE.md`, `GEMINI.md`).

