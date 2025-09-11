<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class GameWerewolfVote
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Game $game;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $voter;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $suspect;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Game $game, User $voter, User $suspect)
    {
        $this->id = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->game = $game;
        $this->voter = $voter;
        $this->suspect = $suspect;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getGame(): Game { return $this->game; }
    public function getVoter(): User { return $this->voter; }
    public function getSuspect(): User { return $this->suspect; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
