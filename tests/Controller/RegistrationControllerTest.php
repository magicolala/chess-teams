<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RegistrationControllerTest extends WebTestCase
{
    public function testRegisterPageLoads(): void
    {
        $client = self::createClient();
        $client->request('GET', '/register');
        self::assertResponseIsSuccessful();
    }
}
