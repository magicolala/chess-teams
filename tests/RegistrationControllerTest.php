<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class RegistrationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->userRepository = self::getContainer()->get(UserRepository::class);
    }

    public function testRegister(): void
    {
        // Register a new user
        $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Create an account');

        $email = 'test+'.bin2hex(random_bytes(4)).'@example.com';
        $this->client->submitForm('🎆 Créer mon compte', [
            'registration_form[email]' => $email,
            'registration_form[displayName]' => 'TestUser_'.bin2hex(random_bytes(2)),
            'registration_form[plainPassword]' => 'password',
            'registration_form[agreeTerms]' => true,
        ]);

        // Ensure the user has been created
        $created = $this->userRepository->findOneBy(['email' => $email]);
        self::assertNotNull($created, 'User should be created');
    }
}
