<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-users',
    description: 'Creates dummy users for testing.',
)]
class CreateTestUsersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('count', InputArgument::OPTIONAL, 'How many users to create?', 10)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io    = new SymfonyStyle($input, $output);
        $count = (int) $input->getArgument('count');

        if ($count <= 0) {
            $io->error('The count must be a positive integer.');

            return Command::INVALID;
        }
        $io->progressStart($count);
        $createdUsersData = [];

        for ($i = 1; $i <= $count; ++$i) {
            $user  = new User();
            $email = sprintf('user%d@test.io', $i + random_int(1000, 9999));
            $user->setEmail($email);
            $user->setDisplayName(sprintf('Test User %d', $i));

            $hashedPassword = $this->passwordHasher->hashPassword($user, 'password');
            $user->setPassword($hashedPassword);
            $this->entityManager->persist($user);
            $createdUsersData[] = ['email' => $email, 'password' => 'password']; // Store email and plain password
            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->success(sprintf('%d test users have been created. Default password is "password".', $count));
        $io->text('Here are the credentials for the created users:');
        $io->table(['Email', 'Password'], $createdUsersData);

        return Command::SUCCESS;
    }
}
