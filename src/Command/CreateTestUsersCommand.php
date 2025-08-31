<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
        // Pas d'argument count car on génère toujours 4 utilisateurs
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = 4; // Toujours 4 utilisateurs

        // Arrays pour générer des noms variés
        $firstNames = [
            'Alice', 'Bob', 'Charlie', 'Diana', 'Eve', 'Frank', 'Grace', 'Henry',
            'Iris', 'Jack', 'Kate', 'Leo', 'Maria', 'Nathan', 'Olivia', 'Paul',
            'Quinn', 'Ruby', 'Sam', 'Tina', 'Uma', 'Victor', 'Wendy', 'Xavier',
            'Yara', 'Zoe', 'Adam', 'Bella', 'Chris', 'Dora',
        ];

        $chessTerms = [
            'Knight', 'Bishop', 'Rook', 'Queen', 'King', 'Pawn', 'Master', 'Grandmaster',
            'Checkmate', 'Castle', 'Gambit', 'Sacrifice', 'Blitz', 'Tactical', 'Strategic',
        ];

        $io->progressStart($count);
        $createdUsersData = [];

        for ($i = 1; $i <= $count; ++$i) {
            $user = new User();
            $email = sprintf('user%d@test.com', $i); // user1@test.com, user2@test.com, etc.

            // Génération de noms variés
            $displayName = $this->generateDisplayName($firstNames, $chessTerms, $i);

            $user->setEmail($email);
            $user->setDisplayName($displayName);

            $hashedPassword = $this->passwordHasher->hashPassword($user, 'password');
            $user->setPassword($hashedPassword);
            $this->entityManager->persist($user);

            $createdUsersData[] = [
                'email' => $email,
                'password' => 'password',
                'displayName' => $displayName,
            ];

            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->success(sprintf('%d test users have been created with default password "password".', $count));
        $io->text('Here are the credentials for the created users:');
        $io->table(['Email', 'Password', 'Display Name'], $createdUsersData);

        return Command::SUCCESS;
    }

    private function generateDisplayName(array $firstNames, array $chessTerms, int $index): string
    {
        $type = $index % 4;

        switch ($type) {
            case 0:
                // Nom + terme d'échecs (ex: "Alice Knight")
                $firstName = $firstNames[array_rand($firstNames)];
                $chessTerm = $chessTerms[array_rand($chessTerms)];

                return $firstName.$chessTerm;

            case 1:
                // Terme d'échecs + nombre (ex: "GrandMaster42")
                $chessTerm = $chessTerms[array_rand($chessTerms)];
                $number = random_int(10, 99);

                return $chessTerm.$number;

            case 2:
                // Nom classique (ex: "Bob")
                return $firstNames[array_rand($firstNames)];

            default:
                // ChessPlayer + nombre (ex: "ChessPlayer87")
                $number = random_int(10, 999);

                return "ChessPlayer{$number}";
        }
    }
}
