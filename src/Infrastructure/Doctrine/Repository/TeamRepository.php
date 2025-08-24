<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Repository\TeamRepositoryInterface;
use App\Entity\Game;
use App\Entity\Team;
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
        $this->getEntityManager()->persist($team);
    }

    public function findOneByGameAndName(Game $game, string $name): ?Team
    {
        return $this->findOneBy(['game' => $game, 'name' => $name]);
    }
}
