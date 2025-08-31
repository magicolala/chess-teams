<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Repository\MoveRepositoryInterface;
use App\Entity\Game;
use App\Entity\Move;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class MoveRepository extends ServiceEntityRepository implements MoveRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Move::class);
    }

    public function add(Move $move): void
    {
        $this->getEntityManager()->persist($move);
    }

    public function countByGame(Game $game): int
    {
        return (int) $this->count(['game' => $game]);
    }

    public function lastPlyByGame(Game $game): int
    {
        $q = $this->createQueryBuilder('m')
            ->select('MAX(m.ply)')
            ->where('m.game = :g')->setParameter('g', $game)
            ->getQuery()->getSingleScalarResult()
        ;

        return null === $q ? -1 : (int) $q;
    }

    public function listByGameOrdered(Game $game): array
    {
        return $this->findBy(['game' => $game], ['ply' => 'ASC']);
    }
}
