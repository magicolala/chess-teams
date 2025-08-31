<?php

namespace App\Entity;

use App\Infrastructure\Doctrine\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_game_name', columns: ['game_id', 'name'])]
class Team
{
    public const NAME_A = 'A';
    public const NAME_B = 'B';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Game $game;

    #[ORM\Column(length: 1)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $currentIndex = 0;

    #[ORM\OneToMany(mappedBy: 'team', targetEntity: TeamMember::class, orphanRemoval: true)]
    private Collection $members;

    public function __construct(Game $game, string $name)
    {
        $this->id = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->game = $game;
        $this->name = $name;
        $this->members = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCurrentIndex(): int
    {
        return $this->currentIndex;
    }

    public function setCurrentIndex(int $i): self
    {
        $this->currentIndex = $i;

        return $this;
    }

    /**
     * @return Collection<int, TeamMember>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(TeamMember $member): self
    {
        if (!$this->members->contains($member)) {
            $this->members[] = $member;
            $member->setTeam($this);
        }

        return $this;
    }

    public function removeMember(TeamMember $member): self
    {
        // set the owning side to null (unless already changed)
        if ($this->members->removeElement($member) && $member->getTeam() === $this) {
            // This would require the team property to be nullable in TeamMember, which might not be desired.
            // orphanRemoval=true will handle the deletion from the DB.
        }

        return $this;
    }
}
