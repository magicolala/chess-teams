<?php
namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Repository\GameRepositoryInterface;
use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class GameRepository extends ServiceEntityRepository implements GameRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    public function add(Game $game): void
    {
        $this->getEntityManager()->persist($game);
    }

    public function get(string $id): ?Game
    {
        return $this->find($id);
    }
}
