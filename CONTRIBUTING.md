# 🤝 Guide de Contribution - Chess-Teams

Merci de votre intérêt pour contribuer à Chess-Teams ! Ce guide vous explique comment participer efficacement au développement du projet.

## 📋 Table des Matières

1. [Code de Conduite](#code-de-conduite)
2. [Comment Commencer](#comment-commencer)
3. [Types de Contributions](#types-de-contributions)
4. [Processus de Développement](#processus-de-développement)
5. [Standards de Codage](#standards-de-codage)
6. [Tests](#tests)
7. [Documentation](#documentation)
8. [Pull Requests](#pull-requests)
9. [Signalement de Bugs](#signalement-de-bugs)
10. [Communauté](#communauté)

## 🤝 Code de Conduite

En participant à ce projet, vous acceptez de respecter notre [Code de Conduite](CODE_OF_CONDUCT.md). Nous nous engageons à maintenir une communauté accueillante et inclusive.

### Nos Valeurs
- **Respect** : Traitez tous les contributeurs avec respect
- **Collaboration** : Travaillez ensemble pour créer quelque chose de formidable
- **Transparence** : Communiquez ouvertement et honnêtement
- **Qualité** : Efforcez-vous de produire du code de haute qualité

## 🚀 Comment Commencer

### Prérequis
- PHP 8.1+
- Composer
- Git
- Compte GitHub
- Connaissance de base de Symfony

### Configuration Initiale

1. **Fork le projet**
   ```bash
   # Via l'interface GitHub, puis clonez votre fork
   git clone https://github.com/VOTRE-USERNAME/chess-teams.git
   cd chess-teams
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   cp .env .env.local
   # Configurez votre base de données locale
   ```

3. **Configurer les hooks Git**
   ```bash
   # Hook pre-commit pour la qualité du code
   echo '#!/bin/sh
   composer cs:check' > .git/hooks/pre-commit
   chmod +x .git/hooks/pre-commit
   ```

4. **Lancer les tests**
   ```bash
   # Local
   ./vendor/bin/phpunit

   # Ou via Docker (conteneur php)
   docker compose exec php ./vendor/bin/phpunit
   ```

## 🔧 Types de Contributions

### 🐛 Corrections de Bugs
- Corrigez des bugs reportés dans les issues
- Ajoutez des tests pour reproduire et valider la correction
- Documentez la correction dans le CHANGELOG

### ✨ Nouvelles Fonctionnalités
- Implémentez de nouvelles fonctionnalités d'échecs
- Améliorez l'interface utilisateur
- Ajoutez des intégrations avec d'autres services

### 📚 Documentation
- Améliorez la documentation existante
- Créez des tutorials et guides
- Traduisez la documentation

### 🧪 Tests
- Ajoutez des tests unitaires et fonctionnels
- Améliorez la couverture de tests
- Créez des tests de performance

### 🎨 Design/UX
- Améliorez l'interface utilisateur
- Créez de nouveaux thèmes pour l'échiquier
- Optimisez l'expérience utilisateur

## 🔄 Processus de Développement

### 1. Choisir une Issue
- Consultez les [issues ouvertes](https://github.com/magicolala/chess-teams/issues)
- Commentez pour indiquer que vous travaillez dessus
- Issues étiquetées `good first issue` pour les nouveaux contributeurs

### 2. Créer une Branche
```bash
# Nommage des branches
git checkout -b type/description-courte

# Exemples :
git checkout -b feature/team-chat
git checkout -b fix/timer-bug
git checkout -b docs/api-examples
```

### 3. Développer
- Suivez les [standards de codage](#standards-de-codage)
- Écrivez des tests pour votre code
- Documentez les changements importants

### 4. Tester
```bash
# Tests unitaires et fonctionnels
./vendor/bin/phpunit
# ou via Docker
docker compose exec php ./vendor/bin/phpunit

# Vérification de la qualité du code
composer cs:check
# ou via Docker
docker compose exec php composer cs:check

# Tests d'intégration
php bin/console doctrine:schema:validate
# ou via Docker
docker compose exec php php bin/console doctrine:schema:validate
```

### 5. Soumettre une Pull Request
- Poussez votre branche vers votre fork
- Créez une PR vers la branche `main`
- Décrivez clairement vos changements

## 📝 Standards de Codage

### PHP

#### PSR-12 + Règles Chess-Teams
```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service pour gérer la logique de jeu d'échecs.
 */
final class ChessGameService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function createGame(array $options = []): Game
    {
        $game = new Game();
        // Configuration du jeu...
        
        $this->entityManager->persist($game);
        $this->entityManager->flush();
        
        return $game;
    }
}
```

#### Conventions de Nommage
- **Classes** : PascalCase (`ChessGameService`)
- **Méthodes** : camelCase (`createGame()`)
- **Variables** : camelCase (`$gameService`)
- **Constantes** : SCREAMING_SNAKE_CASE (`MAX_PLAYERS`)

### JavaScript (Stimulus Controllers)

```javascript
// assets/controllers/chess-game_controller.js
import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
  static values = { gameId: String }
  static targets = ['board', 'timer']
  
  connect() {
    console.debug('[chess-game] Connected', this.gameIdValue)
    this.initializeBoard()
  }
  
  initializeBoard() {
    // Logique d'initialisation de l'échiquier
  }
}
```

### CSS

```css
/* Conventions BEM pour les styles */
.chess-board {
  /* Styles du composant principal */
}

.chess-board__square {
  /* Styles des éléments enfants */
}

.chess-board__square--selected {
  /* Styles des modificateurs */
}

/* Variables CSS pour la cohérence */
:root {
  --chess-primary-color: #4a90e2;
  --chess-secondary-color: #f8f9fa;
}
```

## 🧪 Tests

### Structure des Tests
```
tests/
├── Unit/           # Tests unitaires
│   ├── Entity/
│   ├── Service/
│   └── ...
├── Functional/     # Tests fonctionnels
│   ├── Controller/
│   └── ...
└── Integration/    # Tests d'intégration
    └── ...
```

### Écrire des Tests Unitaires
```php
<?php

namespace App\Tests\Unit\Service;

use App\Service\ChessGameService;
use PHPUnit\Framework\TestCase;

class ChessGameServiceTest extends TestCase
{
    public function testCreateGameWithDefaultOptions(): void
    {
        // Arrange
        $service = new ChessGameService(/* dependencies */);
        
        // Act
        $game = $service->createGame();
        
        // Assert
        $this->assertSame('live', $game->getStatus());
        $this->assertSame('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1', $game->getFen());
    }
}
```

### Tests Fonctionnels
```php
<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GameControllerTest extends WebTestCase
{
    public function testMakeValidMove(): void
    {
        $client = static::createClient();
        
        // Créer une partie de test
        $game = $this->createTestGame();
        
        // Jouer un coup valide
        $client->request('POST', "/games/{$game->getId()}/move", [
            'json' => ['move' => 'e2e4']
        ]);
        
        $this->assertResponseStatusCodeSame(200);
        $this->assertJson($client->getResponse()->getContent());
    }
}
```

### Couverture de Tests
```bash
# Générer un rapport de couverture HTML
./vendor/bin/phpunit --coverage-html coverage/

# Viser au minimum 80% de couverture
./vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml
```

## 📚 Documentation

### DocBlocks PHP
```php
/**
 * Joue un coup dans la partie d'échecs.
 *
 * @param Game   $game La partie d'échecs
 * @param string $move Le coup en notation UCI (ex: "e2e4")
 * 
 * @return MoveResult Le résultat du coup
 * 
 * @throws InvalidMoveException Si le coup est illégal
 * @throws GameFinishedException Si la partie est terminée
 */
public function makeMove(Game $game, string $move): MoveResult
{
    // Implementation...
}
```

### Documentation des API
- Utilisez des annotations Swagger/OpenAPI
- Incluez des exemples de requêtes/réponses
- Documentez les codes d'erreur

### README et Guides
- Mettez à jour le README pour les nouvelles fonctionnalités
- Créez des guides step-by-step
- Incluez des captures d'écran si pertinent

## 📋 Pull Requests

### Template de PR
```markdown
## Description
Description brève des changements apportés.

## Type de changement
- [ ] Bug fix (changement non-breaking qui corrige un problème)
- [ ] Nouvelle fonctionnalité (changement non-breaking qui ajoute une fonctionnalité)
- [ ] Breaking change (correction ou fonctionnalité qui casserait la fonctionnalité existante)
- [ ] Documentation uniquement

## Comment tester
Étapes pour reproduire et tester les changements :
1. Aller à '...'
2. Cliquer sur '....'
3. Faire défiler jusqu'à '....'
4. Voir l'erreur

## Checklist
- [ ] Mon code suit les conventions du projet
- [ ] J'ai effectué une auto-review de mon code
- [ ] J'ai commenté mon code dans les parties difficiles à comprendre
- [ ] J'ai fait les changements correspondants dans la documentation
- [ ] Mes changements ne génèrent pas de nouveaux warnings
- [ ] J'ai ajouté des tests qui prouvent que ma correction est efficace ou que ma fonctionnalité marche
- [ ] Les tests unitaires nouveaux et existants passent localement avec mes changements
```

### Review Process
1. **Automatic Checks** : Les tests et la qualité du code sont vérifiés automatiquement
2. **Peer Review** : Au moins une review d'un maintainer est requise
3. **Testing** : Les changements sont testés sur différents environnements
4. **Merge** : Une fois approuvée, la PR est mergée

## 🐛 Signalement de Bugs

### Template d'Issue de Bug
```markdown
**Description du Bug**
Description claire et concise du problème.

**Étapes pour Reproduire**
1. Aller à '...'
2. Cliquer sur '....'
3. Faire défiler jusqu'à '....'
4. Voir l'erreur

**Comportement Attendu**
Description claire et concise de ce qui devrait se passer.

**Comportement Actuel**
Description de ce qui se passe actuellement.

**Captures d'écran**
Si applicable, ajouter des captures d'écran pour expliquer le problème.

**Environnement :**
 - OS: [ex: Ubuntu 20.04]
 - Navigateur [ex: Chrome, Firefox]
 - Version [ex: 22]
 - PHP Version: [ex: 8.1]
 - Symfony Version: [ex: 6.4]

**Contexte Additionnel**
Ajouter tout autre contexte pertinent au problème.
```

### Priorités des Bugs
- **🔴 Critique** : Crash de l'application, perte de données
- **🟠 Élevé** : Fonctionnalité principale cassée
- **🟡 Moyen** : Fonctionnalité secondaire affectée
- **🟢 Bas** : Problème cosmétique, amélioration

## 💬 Communauté

### Canaux de Communication
- **GitHub Issues** : Pour les bugs et demandes de fonctionnalités
- **GitHub Discussions** : Pour les questions générales et brainstorming
- **Discord** : [Serveur Chess-Teams](https://discord.gg/chess-teams) pour le chat en temps réel

### Réunions Communautaires
- **Weekly Standup** : Tous les lundis à 19h00 (UTC+1)
- **Monthly Planning** : Premier samedi du mois
- **Quarterly Reviews** : Revue des objectifs et roadmap

### Reconnaissance des Contributeurs
- Les contributeurs sont listés dans le fichier CONTRIBUTORS.md
- Badges spéciaux sur Discord pour les contributeurs actifs
- Mentions dans les release notes

## 🏆 Reconnaissance

### Hall of Fame
Nos contributeurs les plus actifs :

- **@magicolala** - Créateur et mainteneur principal
- **@contributor1** - Développement des fonctionnalités d'échecs
- **@contributor2** - Design et UX

### Comment Devenir Mainteneur
1. Contribuer régulièrement et de manière significative
2. Démontrer une bonne compréhension du codebase
3. Aider activement la communauté
4. Être nominé par un mainteneur existant

## 📊 Métriques de Contribution

Nous suivons plusieurs métriques pour comprendre l'impact des contributions :

- **Commits** : Fréquence et qualité des commits
- **Reviews** : Participation aux reviews de code
- **Issues** : Création et résolution d'issues
- **Documentation** : Contributions à la documentation
- **Community** : Aide apportée aux autres contributeurs

## 🚨 Signaler des Problèmes de Sécurité

Pour les vulnérabilités de sécurité, veuillez **NE PAS** créer d'issue publique. 
Contactez-nous directement à : security@chess-teams.com

## 📅 Roadmap

Consultez notre [roadmap publique](https://github.com/magicolala/chess-teams/projects/1) pour voir :
- Les fonctionnalités planifiées
- Les priorités de développement
- Les deadlines importantes

---

## 🙏 Remerciements

Merci à tous les contributeurs qui rendent Chess-Teams possible ! Votre passion pour les échecs et la programmation fait vivre ce projet.

**Questions ?** N'hésitez pas à ouvrir une [discussion GitHub](https://github.com/magicolala/chess-teams/discussions) ou à nous rejoindre sur [Discord](https://discord.gg/chess-teams).

Happy coding! ♟️✨

---

Ressources complémentaires:

- Guide opérationnel & commandes Docker: voir `README.md`.
- Guide détaillé (Windows/PowerShell, bonnes pratiques, dépannage): voir `AGENT_GUIDE.md`.
