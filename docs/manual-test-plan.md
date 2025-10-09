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

### 3.6 Modes avancés et temps réel
- **TC-20 — Lobby et démarrage orchestrés :**
  1. Depuis le lobby, rejoindre successivement les équipes A/B et vérifier les messages de confirmation et les badges d'appartenance.
  2. Basculer l'état "Prêt"/"Pas prêt" pour un joueur et s'assurer de la mise à jour côté interface et flash message.
  3. En tant que créateur, tenter de démarrer sans tous les joueurs prêts (erreur attendue), puis lancer la partie lorsque les deux équipes sont prêtes.【F:templates/game/show.html.twig†L720-L900】【F:src/Controller/GameWebController.php†L201-L319】
- **TC-21 — Activation du mode rapide :**
  1. Pendant une partie longue, déclencher `/games/{id}/enable-fast-mode` (bouton ou appel direct) et vérifier la réponse JSON (`fastModeEnabled`, `fastModeDeadline`, `turnDeadline`).
  2. Confirmer que le timer UI passe en mode rapide (délais raccourcis, badge mis à jour) et que le mode est diffusé aux clients actifs via Mercure/polling.【F:src/Controller/GameController.php†L340-L366】【F:assets/controllers/chess-timer_controller.js†L360-L448】【F:templates/game/show.html.twig†L600-L610】
- **TC-22 — Décision après timeout :**
  1. Simuler un dépassement de temps afin d'afficher le panneau de décision et vérifier que seul le camp concerné voit les actions.
  2. Tester les deux décisions (`end`, `allow_next`) et contrôler le statut et la reprise du tour côté clients (polling + Mercure).【F:templates/game/show.html.twig†L670-L686】【F:src/Controller/GameController.php†L416-L447】【F:assets/controllers/game-board_controller.js†L608-L704】
- **TC-23 — Revendiquer la victoire :**
  1. Provoquer trois timeouts consécutifs de l'adversaire pour rendre disponible l'action "Revendiquer la victoire".
  2. Déclencher l'action et vérifier la réponse (`claimed`, `result`, `winnerTeam`) ainsi que l'arrêt des timers et l'affichage du résultat dans l'UI.【F:templates/game/show.html.twig†L923-L935】【F:src/Controller/GameController.php†L369-L394】【F:assets/controllers/game-poll_controller.js†L160-L175】
- **TC-24 — Notifications multi-canaux :**
  1. Activer/désactiver les toggles bureau/son/flash et vérifier la persistance dans `localStorage` et les événements `chess:notification-settings-changed`.
  2. Lors d'un changement de tour, confirmer la réception des alertes correspondantes (test permission bureau, son de test, flash titre) et la synchronisation avec les préférences profil.【F:templates/game/show.html.twig†L640-L665】【F:assets/controllers/notification-controls_controller.js†L6-L190】【F:assets/controllers/game-poll_controller.js†L20-L85】
- **TC-25 — Synchronisation temps réel :**
  1. Ouvrir la partie sur deux navigateurs ; vérifier que le polling s'arrête lorsque Mercure est connecté et que les coups/états sont propagés instantanément.
  2. Forcer un refresh manuel (`/games/{id}` + `/state`) et s'assurer de la cohérence des données (timer, joueur courant, deadline effective).【F:assets/controllers/game-poll_controller.js†L7-L176】【F:src/Controller/GameController.php†L452-L520】【F:assets/controllers/game-board_controller.js†L647-L704】
- **TC-26 — Mode Hand & Brain :**
  1. Activer le mode via l'endpoint dédié et contrôler l'affectation des rôles cerveau/main dans l'UI.
  2. Définir un indice de pièce (`/hand-brain/hint`) et s'assurer de la mise à jour instantanée du panneau et des restrictions de coups côté main.【F:templates/game/show.html.twig†L500-L544】【F:src/Controller/HandBrainController.php†L16-L70】【F:assets/controllers/game-board_controller.js†L618-L681】
- **TC-27 — Mode Loup-Garou (configuration & rappel) :**
  1. Depuis la création de partie, activer l'option Loup-Garou et, pour ≥6 joueurs, l'option "Un loup par équipe" ; vérifier l'affichage conditionnel de la case supplémentaire.
  2. En partie, confirmer la présence du badge mode, du rappel de rôle individuel et de l'alerte lorsque la phase de vote est ouverte.【F:templates/home/index.html.twig†L49-L70】【F:templates/game/show.html.twig†L568-L996】【F:src/Controller/WerewolfController.php†L30-L103】
- **TC-28 — Phase de vote Loup-Garou :**
  1. Lancer une phase de vote, soumettre un vote et vérifier la mise à jour des totaux en direct côté client.
  2. En tant que créateur/admin, clôturer le vote et valider la propagation de l'état `voteOpen=false` et du résumé des résultats.【F:assets/controllers/werewolf-vote_controller.js†L19-L148】【F:src/Controller/WerewolfController.php†L104-L155】【F:templates/game/show.html.twig†L986-L993】
- **TC-29 — Historique incrémental des coups :**
  1. Jouer plusieurs coups et vérifier que l'endpoint `/games/{id}/moves` ne retourne que les coups valides (SAN/UCI) dans l'ordre.
  2. Contrôler que la liste UI est enrichie sans doublon et que la navigation PGN reste disponible après la fin de partie.【F:src/Controller/GameController.php†L230-L302】【F:assets/controllers/game-board_controller.js†L710-L748】【F:assets/controllers/game-poll_controller.js†L129-L175】

### 3.7 API et intégrations externes
- **TC-30 — Endpoint `/games/{id}/state` :**
  1. Interroger l'API et vérifier la complétude des blocs `timing`, `handBrain`, `claimVictory`, `timeoutDecision`.
  2. Croiser les données avec l'UI (timer, panneaux conditionnels) pour garantir la cohérence de la diffusion temps réel.【F:src/Controller/GameController.php†L452-L520】【F:assets/controllers/game-poll_controller.js†L160-L175】
- **TC-31 — API de vote Werewolf :**
  1. Tester `/games/{id}/votes` sans authentification (refus attendu), en tant que participant (201) puis en tant que non participant (403).
  2. Vérifier `/games/{id}/votes/close` pour le créateur vs un joueur lambda (403) et confirmer que `voteOpen` passe à `false` dans la réponse.【F:src/Controller/WerewolfController.php†L59-L155】【F:assets/controllers/werewolf-vote_controller.js†L24-L142】
- **TC-32 — API Hand & Brain :**
  1. Appeler `/games/{id}/hand-brain/enable` puis `/hint` et vérifier les contrôles d'accès (participants uniquement) et les payloads (`currentRole`, `pieceHint`).
  2. Confirmer que la diffusion Mercure met à jour les clients connectés sans rechargement.【F:src/Controller/HandBrainController.php†L16-L70】【F:assets/controllers/game-board_controller.js†L618-L681】
- **TC-33 — API Fast Mode & timeout :**
  1. Séquencer des appels `enable-fast-mode`, `tick`, `timeout-decision` pour vérifier les transitions de statut et la publication Mercure.
  2. Contrôler les codes HTTP (200/201) et les champs `pending`, `timedOutApplied` selon les scénarios.【F:src/Controller/GameController.php†L340-L447】【F:assets/controllers/chess-timer_controller.js†L360-L470】

## 4. Critères de sortie
- Tous les cas de tests critiques (TC-01 à TC-14) réussissent sans régression majeure.
- Les anomalies bloquantes ou critiques détectées sont consignées et disposent d'un plan de correction.
- Les scénarios complémentaires (TC-15 à TC-19) ont été exécutés ou planifiés pour un cycle ultérieur si des limitations techniques sont identifiées.

## 5. Traçabilité et reporting
- Utiliser un tableau de bord de suivi (ex. Notion, Jira) avec statut Pass/Fail/Blocked pour chaque TC.
- Joindre captures d'écran et éventuels exports PGN aux tickets d'anomalies.
- Reporter en fin de campagne : couverture des tests, anomalies ouvertes, recommandations d'amélioration produit/UX.
