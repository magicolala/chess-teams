<?php

namespace App\Tests\Unit\Command;

use App\Application\DTO\CreateGameOutput;
use App\Application\UseCase\CreateGameHandler;
use App\Command\CreateTestUsersCommand;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CreateTestUsersCommandTest extends TestCase
{
    public function testExecuteCreatesUsersAndGames(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $handler = $this->createMock(CreateGameHandler::class);

        $hasher->method('hashPassword')->willReturn('hashed');

        // CreateGameHandler is invokable; return a simple DTO when called
        $handler
            ->method('__invoke')
            ->willReturnCallback(function ($in, $user) {
                return new CreateGameOutput('gid-'.$user->getEmail(), 'INV123', 60);
            });

        $em->expects(self::atLeastOnce())->method('persist');
        $em->expects(self::once())->method('flush');

        $cmd = new CreateTestUsersCommand($em, $hasher, $handler);
        $output = new BufferedOutput();
        $io = new SymfonyStyle(new ArrayInput([]), $output);

        $status = $cmd($io);

        self::assertSame(Command::SUCCESS, $status);
        $display = $output->fetch();
        self::assertStringContainsString('test users have been created', $display);
        self::assertStringContainsString('Games created for test users', $display);
    }
}
