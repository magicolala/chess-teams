<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\Functional\_AuthTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserProfileControllerTest extends WebTestCase
{
    use _AuthTestTrait;

    private $client;
    private ?EntityManagerInterface $entityManager;
    private ?User $testUser;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine')->getManager();
        $userPasswordHasher = $container->get('security.user_password_hasher');

        // Create a test user
        $this->testUser = new User();
        $this->testUser->setEmail('test@example.com');
        $hashedPassword = $userPasswordHasher->hashPassword($this->testUser, 'password');
        $this->testUser->setPassword($hashedPassword);
        $this->testUser->setDisplayName('Test User');
        $this->testUser->setRoles(['ROLE_USER']);

        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        if ($this->testUser && $this->entityManager) {
            $this->entityManager->remove($this->testUser);
            $this->entityManager->flush();
        }
        $this->entityManager->close();
        $this->entityManager = null;
        $this->testUser = null;
        parent::tearDown();
    }

    public function testProfileRequiresAuthentication(): void
    {
        $this->client->request('GET', '/me');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testNotificationsRequiresAuthentication(): void
    {
        $this->client->request('GET', '/profile/notifications');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testProfilePageWithAuthentication(): void
    {
        $this->loginClient($this->client, $this->testUser);

        $this->client->request('GET', '/me');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('email', $responseData);
        $this->assertArrayHasKey('displayName', $responseData);
        $this->assertArrayHasKey('roles', $responseData);
        $this->assertArrayHasKey('createdAt', $responseData);

        $this->assertEquals($this->testUser->getId(), $responseData['id']);
        $this->assertEquals($this->testUser->getEmail(), $responseData['email']);
        $this->assertEquals($this->testUser->getDisplayName(), $responseData['displayName']);
        $this->assertEquals($this->testUser->getRoles(), $responseData['roles']);
    }

    public function testNotificationsPageWithAuthentication(): void
    {
        $this->loginClient($this->client, $this->testUser);

        $this->client->request('GET', '/profile/notifications');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('notifications', strtolower($this->client->getResponse()->getContent()));
    }
}
