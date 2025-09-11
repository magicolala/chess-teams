<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class GameWerewolfScoreLog
{
    public const REASON_FOUND = 'found_werewolf';
    public const REASON_SUCCESS = 'werewolf_success';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Game $game;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 32)]
    private string $reason;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Game $game, User $user, string $reason)
    {
        $this->id = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->game = $game;
        $this->user = $user;
        $this->reason = $reason;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getGame(): Game { return $this->game; }
    public function getUser(): User { return $this->user; }
    public function getReason(): string { return $this->reason; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
