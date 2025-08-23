<?php
namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\{Game, Team};
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class TeamRepository extends ServiceEntityRepository implements TeamRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    public function add(Team $team): void
    {
        $this->_em->persist($team);
    }

    public function findOneByGameAndName(Game $game, string $name): ?Team
    {
        return $this->findOneBy(['game' => $game, 'name' => $name]);
    }
}
