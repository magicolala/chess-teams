<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SecurityControllerTest extends WebTestCase
{
    public function testLoginPageLoads(): void
    {
        $client = self::createClient();
        $client->request('GET', '/login');
        self::assertResponseIsSuccessful();
    }
}
