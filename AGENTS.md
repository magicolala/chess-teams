# Instructions Agent

- Le projet cible désormais **PHP 8.4+**. Toute contribution doit vérifier la compatibilité (cf. `docs/php84-upgrade.md`).
- Consulte `docs/php84-upgrade.md` avant de modifier la stack (composer, Docker, QA, services applicatifs).
- En cas de refactorisation applicative, privilégier les services dédiés (voir `src/Application/Service/Game/`). Les handlers doivent rester fins (orchestration + DTOs).
- Tests à exécuter avant PR :
  - `composer install`
  - `composer run cs:check`
  - `composer run stan`
  - `./vendor/bin/phpunit`
- Documenter les évolutions de stack ou de workflow dans la documentation correspondante (`docs/`, README, guides Agent/Gemini).
