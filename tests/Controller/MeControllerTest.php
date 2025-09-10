<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MeControllerTest extends WebTestCase
{
    public function testMeRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->request('GET', '/me');
        // Expect a redirect to login or 401 depending on firewall config; accept 302 or 401
        $status = $client->getResponse()->getStatusCode();
        self::assertContains($status, [302, 401]);
    }
}
