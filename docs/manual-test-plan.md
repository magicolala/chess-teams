# Plan de tests manuel Chess-Teams

## 1. Objectif et périmètre
Ce plan couvre les vérifications manuelles à effectuer sur l'application Chess-Teams après une installation réussie. Il s'intéresse aux parcours critiques : authentification, création et gestion de parties, expérience de jeu en direct, notifications et export. Les tests sont pensés pour un environnement de recette utilisant l'infrastructure Docker recommandée.

## 2. Préparation de l'environnement

### 2.1 Prérequis techniques
- PHP 8.4+, Composer et Docker Desktop installés.【F:README.md†L34-L60】
- Accès aux services Docker `database`, `mercure`, `php` et `nginx` décrits dans le guide de démarrage.【F:README.md†L18-L31】
- Extension PHP `ext-sodium` activée afin que l'installation Composer se termine sans erreur (dépendance `lcobucci/jwt`).

### 2.2 Initialisation du projet
1. Cloner le dépôt et copier le fichier `.env` vers `.env.local` en adaptant la base de données.【F:README.md†L48-L69】
2. Installer les dépendances PHP : `composer install`.
3. Lancer l'infrastructure Docker : `docker compose up -d database mercure php nginx`.
4. Initialiser la base : `docker compose exec php php bin/console doctrine:database:create` puis `doctrine:migrations:migrate -n`.
5. Compiler les assets : `docker compose exec php php bin/console asset-map:compile`.
6. Vérifier l'accès à l'application sur `http://localhost:8000` et aux logs des conteneurs.

### 2.3 Comptes et données de test
- Créer deux comptes joueurs (équipe A/B) et un compte supplémentaire pour valider les accès simultanés.
- Préparer un jeu de données de parties : une partie privée, une publique, une partie en mode Loup-Garou, et une partie en cours avec historique de coups.
- Conserver les codes d'invitation générés pour tester les parcours de rejoins.

## 3. Scénarios de test détaillés

### 3.1 Accueil et navigation générale
- **TC-01 — Page d'accueil :**
  1. Ouvrir `/`. Vérifier la présence des sections "Créer une partie", "Rejoindre une partie" et la liste des parties publiques.【F:templates/home/index.html.twig†L10-L88】
  2. Tenter de soumettre le formulaire de création sans durée valide (<10). Attendre un message d'erreur navigateur.
  3. Vérifier que le formulaire "Rejoindre une partie" impose un code obligatoire en majuscules.
- **TC-02 — Navigation globale :**
  1. Depuis le header, accéder aux pages Connexion et Inscription.
  2. Tester les liens de changement de langue (si présents) et le retour vers l'accueil.

### 3.2 Authentification et profil
- **TC-03 — Inscription :**
  1. Accéder à `/register`.
  2. Soumettre le formulaire sans accepter les CGU : vérifier la validation serveur.
  3. Créer un compte valide et confirmer la redirection et le flash de succès.【F:templates/registration/register.html.twig†L7-L102】
- **TC-04 — Connexion et sécurité :**
  1. Tester la connexion avec un mot de passe erroné : affichage de l'alerte d'erreur.【F:templates/security/login.html.twig†L14-L40】
  2. Se connecter avec des identifiants valides et vérifier la présence du bandeau utilisateur connecté.【F:templates/security/login.html.twig†L20-L27】
  3. Tester la déconnexion depuis la page profil.
- **TC-05 — Profil et préférences :**
  1. Accéder à `/profil` connecté : vérifier l'affichage du pseudo, du badge de statut et des boutons vers notifications et déconnexion.【F:templates/user_profile/profile.html.twig†L8-L38】
  2. Sur `/profil/notifications`, activer/désactiver chaque préférence et confirmer les feedbacks UI.【F:templates/user_profile/notifications.html.twig†L8-L94】

### 3.3 Gestion des parties
- **TC-06 — Création de partie standard :**
  1. Depuis l'accueil, créer une partie privée avec durée 60 s/tour.
  2. Vérifier l'affichage du code d'invitation, du statut `lobby` et des équipes vides.【F:templates/game/show.html.twig†L318-L420】
  3. Confirmer que l'organisateur voit le bouton "Démarrer la partie" uniquement quand les joueurs sont prêts.【F:templates/game/show.html.twig†L470-L520】
- **TC-07 — Partie publique :**
  1. Créer une partie publique.
  2. Vérifier sa présence dans la liste "Parties publiques récentes" avec statut et durée.【F:templates/home/index.html.twig†L64-L83】
  3. Ouvrir la page via le lien et confirmer l'accès sans code.
- **TC-08 — Mode Loup-Garou :**
  1. Activer le mode dans le formulaire de création et l'option "Un loup par équipe".
  2. Vérifier côté partie que le panneau des rôles s'affiche et que les rôles sont masqués pour les joueurs non concernés.【F:templates/game/show.html.twig†L132-L220】
- **TC-09 — Rejoindre par code :**
  1. Déconnecter un navigateur/incognito.
  2. Accéder à `/game?code=` avec le code valide ; vérifier la redirection vers la partie ou l'invite à se connecter.
  3. Tester un code invalide et attendre un message d'erreur (flash ou page dédiée).
- **TC-10 — Gestion des équipes :**
  1. En mode lobby, rejoindre l'équipe A puis B et vérifier les badges "Vous" et les statuts (Prêt/En attente).【F:templates/game/show.html.twig†L360-L456】
  2. Utiliser les boutons "Je suis prêt" / "Je ne suis plus prêt" et vérifier la mise à jour des statuts et l'éligibilité au démarrage.【F:templates/game/show.html.twig†L470-L520】
  3. S'assurer que les joueurs non créateurs ne voient pas l'action de démarrage.

### 3.4 Expérience de jeu en direct
- **TC-11 — Timer et notifications :**
  1. Passer la partie en statut `live`.
  2. Observer le composant de timer (décompte, badges d'état).【F:templates/game/show.html.twig†L256-L320】
  3. Activer les notifications de bureau/son/flash et vérifier les autorisations navigateur.【F:templates/game/show.html.twig†L320-L356】
- **TC-12 — Tour par tour :**
  1. Exécuter un coup valide depuis l'équipe active ; vérifier la mise à jour de l'échiquier et de l'historique des coups.【F:templates/game/show.html.twig†L356-L412】
  2. Tenter un coup depuis un joueur hors tour : s'assurer qu'un message d'erreur apparaît (front ou back).
  3. Valider la mise à jour du timer et du prochain joueur.
- **TC-13 — Timeout :**
  1. Laisser expirer le timer et confirmer l'apparition du panneau de décision (victoire ou laisser jouer).【F:templates/game/show.html.twig†L332-L350】
  2. Tester chaque option et vérifier le statut final de la partie.
- **TC-14 — Export & historique :**
  1. Appeler l'endpoint `/games/{id}/pgn` depuis un navigateur authentifié et vérifier la génération d'un fichier PGN complet.【F:README.md†L22-L41】
  2. Vérifier que la liste des coups est correctement ordonnée et annotée par équipe dans l'interface.【F:templates/game/show.html.twig†L356-L412】

### 3.5 Parcours complémentaires
- **TC-15 — Notifications profil vs partie :** comparer la persistance des préférences entre `/profil/notifications` et le panneau en partie.
- **TC-16 — Internationalisation :** changer la langue (si disponible) et vérifier la traduction des libellés principaux.
- **TC-17 — Accessibilité rapide :** tester la navigation clavier sur les formulaires clés (inscription, création de partie) et la présence d'attributs ARIA dans les composants critiques.
- **TC-18 — Résilience réseau :** rafraîchir la page pendant une partie et confirmer la reprise du contexte (échiquier, timer, statut joueurs).
- **TC-19 — Sécurité basique :**
  - Accéder à `/game/{id}` sans être connecté à une partie privée : vérifier le refus.
  - Vérifier que seules les personnes invitées peuvent rejoindre via le code.
  - Tenter d'appeler les actions `markReady`, `decideTimeout` via un utilisateur non autorisé et contrôler la réponse.

## 4. Critères de sortie
- Tous les cas de tests critiques (TC-01 à TC-14) réussissent sans régression majeure.
- Les anomalies bloquantes ou critiques détectées sont consignées et disposent d'un plan de correction.
- Les scénarios complémentaires (TC-15 à TC-19) ont été exécutés ou planifiés pour un cycle ultérieur si des limitations techniques sont identifiées.

## 5. Traçabilité et reporting
- Utiliser un tableau de bord de suivi (ex. Notion, Jira) avec statut Pass/Fail/Blocked pour chaque TC.
- Joindre captures d'écran et éventuels exports PGN aux tickets d'anomalies.
- Reporter en fin de campagne : couverture des tests, anomalies ouvertes, recommandations d'amélioration produit/UX.
