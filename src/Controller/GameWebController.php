<?php

namespace App\Controller;

use App\Application\DTO\CreateGameInput;
use App\Application\UseCase\CreateGameHandler;
use App\Domain\Repository\GameRepositoryInterface;
use App\Domain\Repository\InviteRepositoryInterface;
use App\Domain\Repository\MoveRepositoryInterface;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Team;
use App\Entity\TeamMember;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/app/games', name: 'app_game_')]
final class GameWebController extends AbstractController
{
    public function __construct(
        private GameRepositoryInterface $games,
        private TeamRepositoryInterface $teams,
        private TeamMemberRepositoryInterface $members,
        private MoveRepositoryInterface $moves,
        private CreateGameHandler $createGame,
        private InviteRepositoryInterface $invites,
    ) {
    }

    // POST form créer puis rediriger sur la page
    #[Route('/create', name: 'create_form', methods: ['POST'], options: ['expose' => true])]
    public function create(Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $turn = max(10, min(600, (int) $request->request->get('turnDuration', 60)));
        $vis = $request->request->get('visibility', 'private');

        $out = ($this->createGame)(new CreateGameInput($user->getId() ?? '', $turn, $vis), $user);

        return $this->redirectToRoute('app_game_show_page', ['id' => $out->gameId]);
    }

    // Route spécifique pour /app/games avec paramètre code
    #[Route('', name: 'join_by_code', methods: ['GET'])]
    public function joinByCode(Request $request): Response
    {
        $inviteCode = (string) $request->query->get('code');
        if (!$inviteCode) {
            throw $this->createNotFoundException('code_required');
        }

        $invite = $this->invites->findOneByCode($inviteCode);
        $game = $invite?->getGame();
        if (!$game) {
            throw $this->createNotFoundException('game_not_found');
        }

        return $this->redirectToRoute('app_game_show_page', ['id' => $game->getId()]);
    }

    // Affichage page partie
    #[Route('/{id}', name: 'show_page', methods: ['GET'])]
    public function showPage(Request $request, ?string $id = null): Response
    {
        // Si on vient via ?code=XXXX, on retrouve la partie par code
        if (!$id && $request->query->get('code')) {
            $inviteCode = (string) $request->query->get('code');
            $invite = $this->invites->findOneByCode($inviteCode);
            $game = $invite?->getGame();
            if (!$game) {
                throw $this->createNotFoundException('game_not_found');
            }

            return $this->redirectToRoute('app_game_show_page', ['id' => $game->getId()]);
        }

        $game = $this->games->get($id);
        if (!$game) {
            throw $this->createNotFoundException('game_not_found');
        }

        $teamA = $this->teams->findOneByGameAndName($game, Team::NAME_A);
        $teamB = $this->teams->findOneByGameAndName($game, Team::NAME_B);
        $moves = $this->moves->listByGameOrdered($game);

        // Vérifie si l'utilisateur connecté fait déjà partie de cette partie
        $userMembership = null;
        if ($this->getUser()) {
            $userMembership = $this->members->findOneByGameAndUser($game, $this->getUser());
        }

        // Détermine le joueur actuel qui doit jouer
        $currentPlayer = null;
        if (\App\Entity\Game::STATUS_LIVE === $game->getStatus() && $teamA && $teamB) {
            $currentTeam = 'A' === $game->getTurnTeam() ? $teamA : $teamB;
            $teamMembers = $this->members->findActiveOrderedByTeam($currentTeam);
            if (!empty($teamMembers)) {
                $currentIndex = $currentTeam->getCurrentIndex() % count($teamMembers);
                $currentPlayer = $teamMembers[$currentIndex] ?? null;
            }
        }

        // Vérifie si tous les joueurs sont prêts
        $allReady = $this->members->areAllActivePlayersReady($game);
        $canStart = $allReady && \App\Entity\Game::STATUS_LOBBY === $game->getStatus();

        return $this->render('game/show.html.twig', [
            'game' => $game,
            'teamA' => $teamA,
            'teamB' => $teamB,
            'moves' => $moves,
            'userMembership' => $userMembership,
            'currentPlayer' => $currentPlayer,
            'allReady' => $allReady,
            'canStart' => $canStart,
            'isCreator' => $this->getUser() && $game->getCreatedBy() === $this->getUser(),
            // Données utiles pour JS :
            'initial' => [
                'gameId' => $game->getId(),
                'fen' => $game->getFen(),
                'turnTeam' => $game->getTurnTeam(),
                'turnDeadline' => $game->getTurnDeadline()?->getTimestamp() * 1000,
                'status' => $game->getStatus(),
                'result' => $game->getResult(),
            ],
        ]);
    }

    #[Route('/{id}/join', name: 'join', methods: ['POST'])]
    public function joinTeam(
        string $id,
        Request $request,
        CsrfTokenManagerInterface $csrf,
        EntityManagerInterface $em,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $teamName = (string) $request->request->get('team', '');
        dump($teamName); // DEBUG: Check what teamName is received
        if (!\in_array($teamName, [Team::NAME_A, Team::NAME_B, 'A', 'B'], true)) {
            throw new BadRequestHttpException('invalid_team');
        }
        // normalise
        $teamName = 'A' === $teamName ? Team::NAME_A : ('B' === $teamName ? Team::NAME_B : $teamName);

        $token = new CsrfToken('join-team-'.$id.'-'.(Team::NAME_A === $teamName ? 'A' : 'B'), (string) $request->request->get('_token'));
        if (!$csrf->isTokenValid($token)) {
            throw new BadRequestHttpException('invalid_csrf');
        }

        $game = $this->games->get($id);
        if (!$game) {
            throw new NotFoundHttpException('game_not_found');
        }

        // Récupère l’équipe cible (A/B)
        $team = $this->teams->findOneByGameAndName($game, $teamName);
        if (!$team) {
            $this->addFlash('error', 'Équipe introuvable');

            return $this->redirectToRoute('app_game_show_page', ['id' => $id]);
        }

        // Vérifie si l'utilisateur est déjà membre de la partie (dans l'équipe A ou B)
        $existingAnywhere = $this->members->findOneByGameAndUser($game, $user);
        if ($existingAnywhere) {
            // S'il est déjà dans une équipe, on refuse l'inscription dans une autre équipe.
            $this->addFlash('info', 'Tu es déjà inscrit dans une équipe.');

            return $this->redirectToRoute('app_game_show_page', ['id' => $id]);
        }

        // Position = fin de file pour cette équipe
        $current = $this->members->findActiveOrderedByTeam($team);
        $position = \is_array($current) ? \count($current) : 0;

        $member = new TeamMember($team, $user, $position);
        $member->setActive(true);
        $this->members->add($member);
        $em->flush();

        $this->addFlash('success', \sprintf('Inscription OK dans l’équipe %s', $teamName));

        return $this->redirectToRoute('app_game_show_page', ['id' => $id]);
    }

    #[Route('/{id}/ready', name: 'ready', methods: ['POST'])]
    public function toggleReady(
        string $id,
        Request $request,
        CsrfTokenManagerInterface $csrf,
        EntityManagerInterface $em,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $token = new CsrfToken('toggle-ready-'.$id, (string) $request->request->get('_token'));
        if (!$csrf->isTokenValid($token)) {
            throw new BadRequestHttpException('invalid_csrf');
        }

        $game = $this->games->get($id);
        if (!$game || \App\Entity\Game::STATUS_LOBBY !== $game->getStatus()) {
            throw new NotFoundHttpException('game_not_found_or_not_in_lobby');
        }

        $membership = $this->members->findOneByGameAndUser($game, $user);
        if (!$membership) {
            $this->addFlash('error', 'Vous devez faire partie de la partie pour vous déclarer prêt');

            return $this->redirectToRoute('app_game_show_page', ['id' => $id]);
        }

        $membership->setReadyToStart(!$membership->isReadyToStart());
        $em->flush();

        $readyStatus = $membership->isReadyToStart() ? 'prêt' : 'pas prêt';
        $this->addFlash('success', 'Vous êtes maintenant '.$readyStatus);

        // Vérifier si tout le monde est prêt
        if ($this->members->areAllActivePlayersReady($game)) {
            $this->addFlash('info', 'Tous les joueurs sont prêts ! Le créateur peut maintenant démarrer la partie.');
        }

        return $this->redirectToRoute('app_game_show_page', ['id' => $id]);
    }

    #[Route('/{id}/start-game', name: 'start_game', methods: ['POST'])]
    public function startGame(
        string $id,
        Request $request,
        CsrfTokenManagerInterface $csrf,
        EntityManagerInterface $em,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $token = new CsrfToken('start-game-'.$id, (string) $request->request->get('_token'));
        if (!$csrf->isTokenValid($token)) {
            throw new BadRequestHttpException('invalid_csrf');
        }

        $game = $this->games->get($id);
        if (!$game || \App\Entity\Game::STATUS_LOBBY !== $game->getStatus()) {
            throw new NotFoundHttpException('game_not_found_or_not_ready');
        }

        if ($game->getCreatedBy() !== $user) {
            $this->addFlash('error', 'Seul le créateur peut démarrer la partie');

            return $this->redirectToRoute('app_game_show_page', ['id' => $id]);
        }

        if (!$this->canStartGame($game)) {
            $this->addFlash('error', 'Impossible de démarrer : il faut au moins 1 joueur par équipe et tous doivent être prêts');

            return $this->redirectToRoute('app_game_show_page', ['id' => $id]);
        }

        // Démarrer la partie
        $game->setStatus(\App\Entity\Game::STATUS_LIVE);
        $game->setTurnDeadline(new \DateTimeImmutable('+'.$game->getTurnDurationSec().' seconds'));
        $em->flush();

        $this->addFlash('success', 'Partie démarrée ! C\'est à l\'équipe A de commencer.');

        return $this->redirectToRoute('app_game_show_page', ['id' => $id]);
    }

    private function canStartGame(\App\Entity\Game $game): bool
    {
        return $this->members->areAllActivePlayersReady($game);
    }
}
