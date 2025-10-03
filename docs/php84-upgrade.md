# Migration PHP 8.4

Ce document récapitule la migration du projet Chess-Teams vers PHP 8.4. Il sert de référence rapide pour les contributeurs et les agents IA amenés à travailler sur la stack.

## Résumé des changements

- **Runtime** : exigence PHP portée à 8.4+ (`composer.json`, Dockerfile, documentation utilisateur).
- **Outillage QA** : mise à jour de PHPUnit vers la série 11.x, harmonisation de `phpunit.xml.dist` et des dépendances de dev compatibles PHP 8.4.
- **Services applicatifs** : refactorisation des use cases `StartGame`, `MakeMove` et `TimeoutTick` vers de nouveaux services dédiés (`GameLifecycleService`, `GameMoveService`, `GameTimeoutService`) pour respecter SOLID.
- **Tests** : ajout de batteries de tests unitaires ciblant les nouveaux services et adaptation des tests de handlers.
- **Documentation** : harmonisation des guides (README, INSTALLATION, AGENT_GUIDE, GEMINI, WARP, CONTRIBUTING, CHANGELOG) et ajout de ce mémo.

## Détails techniques

### Dépendances

- `composer.json`
  - `"php": "^8.4"`.
  - `phpunit/phpunit` → `^11.1`.
  - `friendsofphp/php-cs-fixer` → `^3.99`.
  - `symfony/phpunit-bridge` → `^7.1` (compatible PHPUnit 11 / PHP 8.4).
- Regénérer `composer.lock` avec `composer update --lock` après toute mise à jour.

### Conteneurs & runtime

- `docker/php/Dockerfile` utilise désormais l’image de base `php:8.4-fpm-alpine`.
- Alias explicite `Symfony\Component\Lock\LockFactory` → `@lock.factory` dans `config/services.yaml` pour l’autowiring des nouveaux services.

### Qualité & tests

- `phpunit.xml.dist` migré vers le schéma PHPUnit 11 (`https://schema.phpunit.de/11.0/phpunit.xsd`).
- Suppression du listener `SymfonyTestsListener` (obsolète avec PHPUnit 11) au profit du bootstrap personnalisé `tests/bootstrap.php`.
- Nouveaux tests :
  - `tests/Application/Service/Game/GameLifecycleServiceTest.php`
  - `tests/Application/Service/Game/GameMoveServiceTest.php`
  - `tests/Application/Service/Game/GameTimeoutServiceTest.php`
  - Adaptation des tests de handlers (`MakeMoveHandlerTest`, `TimeoutTickHandlerTest`).

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

