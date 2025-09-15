<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'game_role')]
#[ORM\UniqueConstraint(name: 'uniq_game_user_role', columns: ['game_id', 'user_id'])]
class GameRole
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Game $game;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    // 'A' | 'B'
    #[ORM\Column(length: 1)]
    private string $teamName;

    // 'villager' | 'werewolf'
    #[ORM\Column(length: 16)]
    private string $role;

    public function __construct(Game $game, User $user, string $teamName, string $role)
    {
        $this->id = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->game = $game;
        $this->user = $user;
        $this->teamName = $teamName;
        $this->role = $role;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTeamName(): string
    {
        return $this->teamName;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setTeamName(string $t): self
    {
        $this->teamName = $t;

        return $this;
    }

    public function setRole(string $r): self
    {
        $this->role = $r;

        return $this;
    }
}
