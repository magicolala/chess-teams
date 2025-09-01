<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserProfileControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testProfileRequiresAuthentication(): void
    {
        $this->client->request('GET', '/profile');

        $this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testNotificationsRequiresAuthentication(): void
    {
        $this->client->request('GET', '/profile/notifications');

        $this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testProfilePageWithAuthentication(): void
    {
        // Create a mock user
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(123);
        $user->method('getUserIdentifier')->willReturn('test@example.com');
        $user->method('getDisplayName')->willReturn('Test User');

        // Mock the security system
        $this->client->loginUser($user);

        $this->client->request('GET', '/profile');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('profil', strtolower($this->client->getResponse()->getContent()));
    }

    public function testNotificationsPageWithAuthentication(): void
    {
        // Create a mock user
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(456);
        $user->method('getUserIdentifier')->willReturn('test@example.com');
        $user->method('getDisplayName')->willReturn('Test User');

        // Mock the security system
        $this->client->loginUser($user);

        $this->client->request('GET', '/profile/notifications');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('notifications', strtolower($this->client->getResponse()->getContent()));
    }
}
