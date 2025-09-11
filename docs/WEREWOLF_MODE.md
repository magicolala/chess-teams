# Mode Loup-Garou — Spécification Métier et Choix de Conception

Ce document décrit la fonctionnalité « Mode Loup-Garou » du projet `chess-teams` sous l’angle métier, ainsi que les choix de conception effectués pour chaque point nécessitant une décision.

## Objectif
- Ajouter un mode optionnel où, à partir de 4 joueurs, un rôle secret de loup-garou est attribué.
- Le(s) loup(s)-garou(s) doit/doivent faire perdre son/leur équipe en jouant des mauvais coups.
- À la fin de la partie, une phase de vote permet aux joueurs de désigner un suspect.
- Attribution de points individuels selon le résultat du vote et l’issue de la partie.

## Terminologie
- Joueur: une personne participant à la partie.
- Équipe: `A` ou `B`, chaque équipe ayant plusieurs joueurs.
- Rôle: `villager` (villageois) ou `werewolf` (loup-garou).
- Vote: chaque joueur participant choisit un et un seul suspect.

## Activation du mode
- Sélectionnable uniquement à la création de la partie et verrouillé ensuite.
- Condition: nombre total de joueurs ≥ 4.
- Si la partie descend ensuite sous 4 joueurs, le mode reste actif.

## Attribution des rôles
- 4–5 joueurs: exactement 1 loup-garou dans l’ensemble de la partie (équipe aléatoire).
- ≥6 joueurs: choix de conception — Option B (paramétrable). Par défaut 1 loup-garou; le créateur peut activer l’option « 1 loup par équipe » (donc 2 loups au total, un dans `A`, un dans `B`).
- Assignation: strictement aléatoire et secrète; chaque joueur connaît uniquement son propre rôle. Les API publiques ne divulguent jamais les rôles; un endpoint admin (restreint) peut consulter les rôles.
- Contrainte: avec l’option « 1 par équipe », on assigne au plus 1 loup dans chaque équipe. En cas d’équipes déséquilibrées (ex. 4 vs 2), on garde 1 par équipe (si l’option est active), tant que chaque équipe a ≥1 joueur.

## Fin de partie et phase de vote
- Le vote a lieu uniquement après la fin de la partie (quel que soit le motif: checkmate, resign, timeout, claim, draw).
- Qui peut voter: tous les joueurs participants (les spectateurs sont exclus).
- 1 vote par joueur, visible (non anonyme) en direct.
- Clôture du vote: dès que tous les participants ont voté. (Un bouton « forcer la clôture » admin pourra être ajouté plus tard si un joueur ne vote pas.)

## Définition de la majorité
- Majorité des votes exprimés (bulletins déposés), pas majorité absolue des participants.
- Égalité: résultat nul du vote (voir scoring ci-dessous).

## Scoring individuel
- Classement global individuel (par utilisateur), avec historique par partie.
- Règles générales:
  - Si la majorité désigne un loup-garou (peu importe lequel s’il y en a deux):
    - +1 point pour chaque joueur qui a voté exactement pour ce loup (vote correct). Les joueurs qui se trompent ne marquent pas.
    - Le(s) loup(s) ne marque(nt) pas de point dans ce cas (considéré « démasqué(s) »).
  - Si aucune majorité ne désigne un loup (égalité, dispersion des votes) ET si l’équipe du loup perd la partie:
    - +1 point pour le loup de l’équipe perdante (succès). S’il y a 2 loups, seul le loup de l’équipe perdante marque.
  - Nulle (draw):
    - On ouvre la phase de vote.
    - Si une majorité identifie un loup: +1 point aux votants corrects. Aucun point pour les loups (ils n’ont pas réussi à faire perdre leur équipe).
    - En cas d’absence de majorité: pas de point par défaut. Exception « personne ne vote » ci-dessous.
  - Personne ne vote (cas extrême): +1 point pour le(s) loup(s) de l’équipe perdante. Si la partie est nulle et qu’il n’y a strictement aucun vote, on attribue +1 point au(x) loup(s) de chaque équipe active (exception assumée pour éviter la triche par abstention générale). Ce choix suit la spécification « Point pour le loup garou si personne ne vote ».

Notes de conception:
- Avec 2 loups (1 par équipe), si la majorité identifie l’un des deux, seuls les votants de ce loup marquent +1. Il n’y a pas de cumul des voix sur « loup A » + « loup B » pour créer une majorité.
- Si l’équipe du loup A perd, seul le loup A peut marquer via la condition « succès du loup » (A a provoqué la défaite). Le loup B ne marque pas par succès si son équipe ne perd pas.

## Rappel pendant la partie
- L’interface rappelle le rôle de manière individuelle (chaque joueur voit son rôle), aucun indicateur global ni indices sur d’autres rôles.

## Sécurité et permissions
- Seuls les participants peuvent voter (vérifié côté serveur).
- Les rôles ne sont jamais exposés dans les payloads publics ni dans les logs côté client.
- Un admin applicatif peut consulter les rôles et les résultats détaillés de vote.

## Données & Persistance (schéma logique)
- `Game.mode` enum: `classic` | `werewolf`.
- `GameRole` (one-to-many par partie):
  - `game_id` (FK), `user_id` (FK), `team_name` (`A`/`B`), `role` enum {`villager`,`werewolf`}
  - Contrainte: unique(`game_id`,`user_id`). Contrainte logique: ≤1 loup par équipe quand l’option est active.
- `GameWerewolfVote` (one-to-many par partie):
  - `game_id` (FK), `voter_user_id` (FK), `suspect_user_id` (FK), `created_at`
  - Contrainte: unique(`game_id`,`voter_user_id`).
- `UserWerewolfStats` (agrégats globaux):
  - `user_id` (FK), `correct_identifications` (int), `werewolf_successes` (int), `updated_at`.
- `GameWerewolfScoreLog` (historique):
  - `game_id` (FK), `user_id` (FK), `reason` enum {`found_werewolf`,`werewolf_success`}, `created_at`.
- `Game` (indicateurs de phase de vote):
  - `vote_open` (bool), éventuellement `vote_started_at`. (Facultatif côté M1 — calcul “tout le monde a voté” peut se faire via comptage.)

## Règles métier (synthèse)
1. Création de partie:
   - Toggle « Mode Loup-Garou » (désactivé si joueurs < 4).
   - Sous-option (si ≥ 6 joueurs): « 1 loup par équipe » (désactivée par défaut). Si actif: assigne 2 loups.
2. Démarrage:
   - Assignation aléatoire des rôles selon l’option choisie.
   - Chaque joueur peut interroger un endpoint pour connaître son rôle.
3. En cours de partie:
   - Rappel discret du rôle pour le joueur courant. Rien d’autre.
4. Fin de partie:
   - Ouvrir la phase de vote.
   - Collecter les votes visibles.
   - Clôturer dès que tous les participants ont voté.
   - Calculer la majorité (votes exprimés). En cas d’égalité: pas de majorité.
   - Distribuer les points selon les règles de scoring.
   - Enregistrer `GameWerewolfScoreLog` et mettre à jour `UserWerewolfStats`.

## API (contrats de haut niveau)
- POST `/games` — créer une partie avec `{ mode: 'classic'|'werewolf', twoWolvesPerTeams: boolean }`.
- GET `/games/{id}/me/role` — rôle du joueur courant (authentifié) pour cette partie.
- POST `/games/{id}/votes` — `{ suspectUserId }` (1 vote par joueur, validation serveur, visible en direct).
- GET `/games/{id}/votes` — état courant des votes (liste des bulletins, visible côté participants; endpoint admin peut voir tout) .
- POST `/games/{id}/votes/close` — clôture administrative (optionnelle pour M1).

## UI/UX
- Création: toggle « Mode Loup-Garou » (+ sous-option « 1 loup par équipe » si ≥6 joueurs).
- Page partie (`templates/game/show.html.twig`): badge « Loup-Garou » si actif, rappel individuel du rôle.
- Fin de partie: modale de vote, liste des joueurs, progression du nombre de votes déposés, résultats affichés à la clôture.

## Cas limites (edge cases)
- Égalité en tête sur plusieurs suspects: pas de majorité, donc pas de point « trouveur ». On évalue ensuite le succès des loups selon l’issue (défaite de leur équipe) ou l’exception « personne ne vote ».
- Joueurs quittent en cours de partie: le mode reste actif.
- Aucune participation au vote: attribuer +1 au(x) loup(s) de l’équipe perdante; en cas de nulle sans vote: +1 à chaque loup (exception pour dissuader l’abstention collective).

## Implémentation — plan (MVP)
1. Modèle & migrations Doctrine:
   - `Game.mode` + tables `GameRole`, `GameWerewolfVote`, `UserWerewolfStats`, `GameWerewolfScoreLog`.
2. Services application:
   - `WerewolfRoleAssigner` (assignation aléatoire, 1 ou 2 loups).
   - `WerewolfVoteService` (ouverture/collecte/clôture, calcul de majorité).
   - `WerewolfScoringService` (distribution des points, mise à jour agrégats + logs).
3. Contrôleurs & endpoints selon API ci-dessus.
4. UI minimale: toggle de création, badge, modale de vote.
5. Tests: assignation (simple/multiple), vote (majorité/égalité), scoring (victoire/défaite/nulle, personne-ne-vote), permissions.

## Choix tranchés (résumé)
- Deux loups à partir de 6 joueurs: option paramétrable à la création, désactivée par défaut.
- Majorité: des votes exprimés. Égalité => pas de majorité.
- Points « trouveur »: uniquement pour les votants corrects (pas tous les non-loups).
- Succès du loup: si son équipe perd ET pas de majorité contre lui. Avec 2 loups, seul le loup de l’équipe perdante marque.
- Nulle: permet des points « trouveur » si majorité; sinon pas de points, sauf exception « personne ne vote ».
- Personne ne vote: point(s) pour le(s) loup(s) de l’équipe perdante; en cas de nulle sans vote, +1 pour chaque loup.
