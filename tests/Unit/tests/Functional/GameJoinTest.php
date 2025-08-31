<?php

namespace App\Tests\Functional;

use App\Application\DTO\CreateGameInput;
use App\Application\UseCase\CreateGameHandler;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class GameJoinTest extends WebTestCase
{
    use _AuthTestTrait;

    public function testJoinByCodeWorks(): void
    {
        $client = self::createClient();
        $c = self::getContainer();
        $em = $c->get('doctrine')->getManager();

        $u1 = new User();
        $u1->setEmail('u1+'.bin2hex(random_bytes(4)).'@test.io');
        $u1->setPassword(password_hash('x', PASSWORD_BCRYPT));
        $em->persist($u1);

        $u2 = new User();
        $u2->setEmail('u2+'.bin2hex(random_bytes(4)).'@test.io');
        $u2->setPassword(password_hash('x', PASSWORD_BCRYPT));
        $em->persist($u2);

        $em->flush();

        // créer une partie via use case (plus simple que passer par HTTP ici)
        /** @var CreateGameHandler $create */
        $create = $c->get(CreateGameHandler::class);
        $out = $create(new CreateGameInput($u1->getId() ?? 'uid', 60, 'private'), $u1);

        // login as u2
        $this->loginClient($client, $u2);

        // join via HTTP
        $client->request('POST', '/games/join/'.$out->inviteCode);
        $this->assertResponseIsSuccessful();

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertTrue($json['ok']);
        self::assertContains($json['team'], ['A', 'B']);
        self::assertIsInt($json['position']);
    }
}
