<?php

namespace App\Tests\Functional;

use App\Application\DTO\CreateGameInput;
use App\Application\UseCase\CreateGameHandler;
use App\Domain\Repository\TeamMemberRepositoryInterface;
use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\Move;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @coversNothing
 */
final class ManualPlanCoverageTest extends WebTestCase
{
    use _AuthTestTrait;

    public function testHomePageDisplaysKeySectionsAndFormConstraints(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('Créer une partie', $content);
        self::assertStringContainsString('Rejoindre une partie', $content);
        self::assertStringContainsString('Parties publiques récentes', $content);

        $durationInput = $crawler->filter('input#turnDuration');
        self::assertSame('10', $durationInput->attr('min'));
        self::assertSame('600', $durationInput->attr('max'));

        $twoWolvesGroup = $crawler->filter('#twoWolvesGroup');
        self::assertSame('display:none;', str_replace(' ', '', $twoWolvesGroup->attr('style') ?? ''));

        $codeInput = $crawler->filter('input#invitationCode');
        self::assertNotNull($codeInput->attr('required'));
        self::assertStringContainsString('uppercase', $codeInput->attr('style'));
    }

    public function testRegistrationFlowValidationAndSuccess(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/register');
        $email = 'player'.bin2hex(random_bytes(4)).'@example.test';
        $form = $crawler->selectButton('🎆 Créer mon compte')->form([
            'registration_form[email]' => $email,
            'registration_form[plainPassword]' => 'StrongPass1',
            'registration_form[displayName]' => 'Player One',
        ]);

        $crawler = $client->submit($form);

        $status = $client->getResponse()->getStatusCode();
        self::assertContains($status, [Response::HTTP_OK, Response::HTTP_UNPROCESSABLE_ENTITY], sprintf('Unexpected status code %d after validation failure.', $status));
        self::assertSelectorExists('.neo-alert-error');
        self::assertSelectorTextContains('body', 'You should agree to our terms.');

        $form = $crawler->selectButton('🎆 Créer mon compte')->form([
            'registration_form[email]' => $email,
            'registration_form[plainPassword]' => 'StrongPass1',
            'registration_form[displayName]' => 'Player One',
            'registration_form[agreeTerms]' => '1',
        ]);

        $client->submit($form);
        self::assertResponseRedirects('/');

        $client->followRedirect();
        self::assertSelectorTextContains('.neo-flash-success', 'Votre compte a été créé avec succès');
        self::assertSelectorTextContains('.neo-user-name', 'Player One');
    }

    public function testLoginFlowShowsErrorAndSuccess(): void
    {
        $client = self::createClient();
        $container = self::getContainer();
        $em = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('login+'.bin2hex(random_bytes(4)).'@example.test');
        $user->setDisplayName('Login Tester');
        $user->setPassword(password_hash('Correct#123', PASSWORD_BCRYPT));
        $em->persist($user);
        $em->flush();

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('🚀 Se connecter')->form([
            '_username' => $user->getEmail(),
            '_password' => 'wrong-password',
        ]);

        $client->submit($form);
        self::assertResponseRedirects('/login');

        $crawler = $client->followRedirect();
        self::assertSelectorExists('.neo-alert-error');
        self::assertMatchesRegularExpression('/(Invalid credentials|Identifiants invalides)/', $client->getResponse()->getContent());

        $form = $crawler->selectButton('🚀 Se connecter')->form([
            '_username' => $user->getEmail(),
            '_password' => 'Correct#123',
        ]);
        $client->submit($form);

        self::assertResponseRedirects('/');
        $client->followRedirect();
        self::assertSelectorTextContains('.neo-user-name', 'Login Tester');
    }

    public function testJoinByCodeRedirectsAndInvalidCodeFails(): void
    {
        $client = self::createClient();
        $container = self::getContainer();
        $em = $container->get('doctrine')->getManager();

        $creator = new User();
        $creator->setEmail('host+'.bin2hex(random_bytes(4)).'@example.test');
        $creator->setDisplayName('Game Host');
        $creator->setPassword(password_hash('Secret#1', PASSWORD_BCRYPT));
        $em->persist($creator);
        $em->flush();

        /** @var CreateGameHandler $create */
        $create = $container->get(CreateGameHandler::class);
        $out = $create(new CreateGameInput($creator->getId() ?? '', 60, 'private'), $creator);

        $this->loginClient($client, $creator);

        $client->request('GET', '/app/games?code='.$out->inviteCode);
        self::assertResponseRedirects('/app/games/'.$out->gameId);

        $crawler = $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', "Code d'invitation");

        $client->request('GET', '/app/games?code=INVALIDCODE');
        self::assertResponseStatusCodeSame(404);
    }

    public function testCreatorSeesStartButtonOnlyWhenTeamsReady(): void
    {
        $client = self::createClient();
        $container = self::getContainer();
        $em = $container->get('doctrine')->getManager();

        $creator = new User();
        $creator->setEmail('creator+'.bin2hex(random_bytes(4)).'@example.test');
        $creator->setDisplayName('Creator');
        $creator->setPassword(password_hash('Secret#2', PASSWORD_BCRYPT));

        $opponent = new User();
        $opponent->setEmail('opponent+'.bin2hex(random_bytes(4)).'@example.test');
        $opponent->setDisplayName('Opponent');
        $opponent->setPassword(password_hash('Secret#3', PASSWORD_BCRYPT));

        $em->persist($creator);
        $em->persist($opponent);
        $em->flush();

        /** @var CreateGameHandler $create */
        $create = $container->get(CreateGameHandler::class);
        $output = $create(new CreateGameInput($creator->getId() ?? '', 60, 'private'), $creator);

        $game = $em->getRepository(Game::class)->find($output->gameId);
        self::assertInstanceOf(Game::class, $game);

        /** @var TeamRepositoryInterface $teams */
        $teams = $container->get(TeamRepositoryInterface::class);
        $teamA = $teams->findOneByGameAndName($game, Team::NAME_A);
        $teamB = $teams->findOneByGameAndName($game, Team::NAME_B);
        self::assertNotNull($teamA);
        self::assertNotNull($teamB);

        /** @var TeamMemberRepositoryInterface $members */
        $members = $container->get(TeamMemberRepositoryInterface::class);
        $memberA = (new TeamMember($teamA, $creator, 0))->setReadyToStart(false);
        $memberB = (new TeamMember($teamB, $opponent, 0))->setReadyToStart(false);
        $members->add($memberA);
        $members->add($memberB);
        $em->flush();

        $this->loginClient($client, $creator);

        $gameId = $game->getId();
        $crawler = $client->request('GET', '/app/games/'.$gameId);
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('form[action="/app/games/'.$gameId.'/start-game"]');

        $memberA->setReadyToStart(true);
        $memberB->setReadyToStart(true);
        $em->flush();

        $client->request('GET', '/app/games/'.$gameId);
        self::assertSelectorExists('form[action="/app/games/'.$gameId.'/start-game"] button');
        self::assertSelectorTextContains('form[action="/app/games/'.$gameId.'/start-game"] button', 'Démarrer la partie');
    }

    public function testPgnExportReturnsStructuredContent(): void
    {
        $client = self::createClient();
        $container = self::getContainer();
        $em = $container->get('doctrine')->getManager();

        $creator = new User();
        $creator->setEmail('pgn+'.bin2hex(random_bytes(4)).'@example.test');
        $creator->setDisplayName('PGN Host');
        $creator->setPassword(password_hash('Secret#4', PASSWORD_BCRYPT));
        $em->persist($creator);
        $em->flush();

        /** @var CreateGameHandler $create */
        $create = $container->get(CreateGameHandler::class);
        $output = $create(new CreateGameInput($creator->getId() ?? '', 60, 'public'), $creator);

        $game = $em->getRepository(Game::class)->find($output->gameId);
        self::assertInstanceOf(Game::class, $game);

        /** @var TeamRepositoryInterface $teams */
        $teams = $container->get(TeamRepositoryInterface::class);
        $teamA = $teams->findOneByGameAndName($game, Team::NAME_A);
        $teamB = $teams->findOneByGameAndName($game, Team::NAME_B);
        self::assertNotNull($teamA);
        self::assertNotNull($teamB);

        $move1 = (new Move($game, 1))
            ->setTeam($teamA)
            ->setSan('e4')
            ->setFenAfter('fen-after-e4');
        $move2 = (new Move($game, 2))
            ->setTeam($teamB)
            ->setSan('e5')
            ->setFenAfter('fen-after-e5');
        $em->persist($move1);
        $em->persist($move2);
        $em->flush();

        $client->request('GET', '/games/'.$game->getId().'/pgn');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/x-chess-pgn; charset=utf-8');
        self::assertStringContainsString('[Event "Chess Teams Game"]', $client->getResponse()->getContent());
        self::assertStringContainsString('1. e4 e5 *', $client->getResponse()->getContent());
    }
}
