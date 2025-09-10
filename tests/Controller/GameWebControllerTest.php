<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GameWebControllerTest extends WebTestCase
{
    public function testShowPageNotFound(): void
    {
        $client = self::createClient();
        $client->request('GET', '/app/games/nonexistent-id');
        self::assertResponseStatusCodeSame(404);
    }
}
