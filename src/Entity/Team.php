<?php

namespace App\Entity;

use App\Repository\TeamRepository;
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

    public function __construct(Game $game, string $name)
    {
        $this->id = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->game = $game;
        $this->name = $name;
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
}
