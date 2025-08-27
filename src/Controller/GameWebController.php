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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
        $vis  = $request->request->get('visibility', 'private');

        $out = ($this->createGame)(new CreateGameInput($user->getId() ?? '', $turn, $vis), $user);

        return $this->redirectToRoute('app_game_show_page', ['id' => $out->gameId]);
    }

    // Affichage page partie
    #[Route('/{id}', name: 'show_page', methods: ['GET'])]
    public function showPage(Request $request, ?string $id = null): Response
    {
        // Si on vient via ?code=XXXX, on retrouve la partie par code
        if (!$id && $request->query->get('code')) {
            $inviteCode = (string) $request->query->get('code');
            $invite     = $this->invites->findOneByCode($inviteCode);
            $game       = $invite?->getGame();
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

        return $this->render('game/show.html.twig', [
            'game'  => $game,
            'teamA' => $teamA,
            'teamB' => $teamB,
            'moves' => $moves,
            // Données utiles pour JS :
            'initial' => [
                'gameId'       => $game->getId(),
                'fen'          => $game->getFen(),
                'turnTeam'     => $game->getTurnTeam(),
                'turnDeadline' => $game->getTurnDeadline()?->getTimestamp() * 1000,
                'status'       => $game->getStatus(),
                'result'       => $game->getResult(),
            ],
        ]);
    }
}
