<?php

namespace App\Tests\Unit\Security;

use App\Security\LoginFormAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class LoginFormAuthenticatorTest extends TestCase
{
    public function testGetLoginUrlUsesRoute(): void
    {
        $urlGen = $this->createMock(UrlGeneratorInterface::class);
        $urlGen->method('generate')->with(LoginFormAuthenticator::LOGIN_ROUTE)->willReturn('/login');
        $auth = new LoginFormAuthenticator($urlGen);

        $req = Request::create('/login');
        $url = (new \ReflectionClass($auth))->getMethod('getLoginUrl')->invoke($auth, $req);
        self::assertSame('/login', $url);
    }

    public function testAuthenticateBuildsPassport(): void
    {
        $urlGen = $this->createMock(UrlGeneratorInterface::class);
        $auth = new LoginFormAuthenticator($urlGen);

        $req = Request::create('/login', 'POST', server: [], content: json_encode([
            'email' => 'user@test.io',
            'password' => 'secret',
            '_csrf_token' => 'token',
        ]));
        $req->setRequestFormat('json');
        $req->setSession(new \Symfony\Component\HttpFoundation\Session\Session(new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage()));

        $passport = $auth->authenticate($req);
        self::assertInstanceOf(\Symfony\Component\Security\Http\Authenticator\Passport\Passport::class, $passport);
    }

    public function testOnAuthenticationSuccessThrowsUntilImplemented(): void
    {
        $this->expectException(\Exception::class);
        $urlGen = $this->createMock(UrlGeneratorInterface::class);
        $auth = new LoginFormAuthenticator($urlGen);
        $req = Request::create('/login');
        $req->setSession(new \Symfony\Component\HttpFoundation\Session\Session(new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage()));
        $auth->onAuthenticationSuccess($req, $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class), 'main');
    }
}
