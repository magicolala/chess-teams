<?php

namespace App\Tests\Functional;

use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

/**
 * @internal
 *
 * @coversNothing
 */
trait _AuthTestTrait
{
    private function loginClient($client, \App\Entity\User $user, string $firewallName = 'main'): void
    {
        $container = static::getContainer();
        $session = $container->get('session.factory')->createSession();
        $token = new PostAuthenticationToken($user, $firewallName, $user->getRoles());
        $session->set('_security_'.$firewallName, serialize($token));
        $session->save();
        $client->getCookieJar()->set(new Cookie($session->getName(), $session->getId()));
    }
}
