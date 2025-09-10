<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Repository\GameRepositoryInterface;
use App\Entity\Game;
use App\Entity\Invite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\Uuid;

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
        // Avoid DBAL ConversionException when an arbitrary string is passed as ID
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->find($id);
    }

    public function findOneByInviteCode(string $code): ?Game
    {
        return $this->createQueryBuilder('g')
            ->innerJoin(Invite::class, 'i', 'WITH', 'i.game = g')
            ->where('i.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return Game[]
     */
    public function findPublicGames(int $limit = 10): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.visibility = :visibility')
            ->setParameter('visibility', 'public')
            ->orderBy('g.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }
}
