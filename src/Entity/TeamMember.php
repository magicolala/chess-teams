<?php

namespace App\Entity;

use App\Infrastructure\Doctrine\Repository\TeamMemberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamMemberRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_team_user', columns: ['team_id', 'user_id'])]
class TeamMember
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Team $team;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $joinedAt;

    public function __construct(Team $team, User $user, int $position)
    {
        $this->id       = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->team     = $team;
        $this->user     = $user;
        $this->position = $position;
        $this->joinedAt = new \DateTimeImmutable();
        $this->active   = true;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTeam(): Team
    {
        return $this->team;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $p): self
    {
        $this->position = $p;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $a): self
    {
        $this->active = $a;

        return $this;
    }

    public function getJoinedAt(): \DateTimeImmutable
    {
        return $this->joinedAt;
    }
}
