<?php
namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Repository\InviteRepositoryInterface;
use App\Entity\{Game, Invite};
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class InviteRepository extends ServiceEntityRepository implements InviteRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invite::class);
    }

    public function add(Invite $invite): void
    {
        $this->_em->persist($invite);
    }

    public function findOneByGame(Game $game): ?Invite
    {
        return $this->findOneBy(['game' => $game]);
    }
}
