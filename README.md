# Chess-Teams

`Chess-Teams` est une application web pour jouer aux échecs en équipe. Elle permet à plusieurs joueurs de collaborer au sein de deux équipes (Blancs et Noirs) pour décider du meilleur coup à jouer. L'application inclut un échiquier interactif en temps réel, un historique des coups et un minuteur pour chaque tour.

## Fonctionnalités

-   Jeu d'échecs en équipe.
-   Échiquier interactif en temps réel grâce à Chessground.
-   Validation des coups et gestion de l'état du jeu avec Chess.js.
-   Minuteur de tour avec indicateurs visuels d'urgence.
-   Mise à jour en direct de la liste des coups.
-   Backend robuste construit avec le framework Symfony.

## Stack Technique

-   **Backend**: PHP, Symfony, Doctrine ORM
-   **Frontend**: JavaScript, Stimulus, Webpack Encore
-   **Base de données**: Base de données SQL (ex: PostgreSQL, MySQL)
-   **Tests**: PHPUnit

## Prérequis

-   PHP 8.1 ou supérieur
-   Composer
-   Symfony CLI
-   Node.js et npm (ou yarn)

## Installation

1.  Clonez le dépôt :
    ```bash
    git clone https://github.com/votre-utilisateur/chess-teams.git
    cd chess-teams
    ```

2.  Installez les dépendances PHP :
    ```bash
    composer install
    ```

3.  Installez les dépendances frontend :
    ```bash
    npm install
    # ou
    yarn install
    ```

4.  Compilez les assets frontend :
    ```bash
    npm run build
    # ou pour le développement avec surveillance des fichiers
    npm run watch
    ```

5.  Configurez vos variables d'environnement. Copiez `.env` vers `.env.local` et personnalisez-le, notamment la `DATABASE_URL` :
    ```bash
    cp .env .env.local
    # ouvrez .env.local et modifiez DATABASE_URL
    ```

6.  Initialisez la base de données :
    ```bash
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    ```

7.  Démarrez le serveur web local :
    ```bash
    symfony server:start -d
    ```
    L'application devrait être accessible à l'adresse `https://127.0.0.1:8000`.

## Lancer les tests

Pour exécuter les tests fonctionnels et unitaires, utilisez PHPUnit :
```bash
./vendor/bin/phpunit
```