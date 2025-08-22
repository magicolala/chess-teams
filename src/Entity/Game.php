<?php

namespace App\Entity;

use App\Entity\Enum\GameStatus;
use App\Entity\Enum\TeamColor;
use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, options: ["default" => "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1"])]
    private ?string $fen = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

    #[ORM\Column(type: 'string', enumType: GameStatus::class)]
    private ?GameStatus $status;

    #[ORM\Column(type: 'string', enumType: TeamColor::class)]
    private ?TeamColor $currentTurn = TeamColor::WHITE;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastMoveAt = null;

    #[ORM\OneToMany(mappedBy: 'game', targetEntity: Team::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $teams;

    #[ORM\OneToMany(mappedBy: 'game', targetEntity: Move::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $moves;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    private ?Team $winner = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = GameStatus::WAITING_FOR_PLAYERS;
        $this->teams = new ArrayCollection();
        $this->moves = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFen(): ?string
    {
        return $this->fen;
    }

    public function setFen(string $fen): static
    {
        $this->fen = $fen;
        return $this;
    }

    public function getStatus(): ?GameStatus
    {
        return $this->status;
    }

    public function setStatus(GameStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCurrentTurn(): ?TeamColor
    {
        return $this->currentTurn;
    }

    public function setCurrentTurn(TeamColor $currentTurn): static
    {
        $this->currentTurn = $currentTurn;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastMoveAt(): ?\DateTimeInterface
    {
        return $this->lastMoveAt;
    }

    public function setLastMoveAt(?\DateTimeInterface $lastMoveAt): static
    {
        $this->lastMoveAt = $lastMoveAt;
        return $this;
    }

    /**
     * @return Collection<int, Team>
     */
    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function addTeam(Team $team): static
    {
        if (!$this->teams->contains($team)) {
            $this->teams->add($team);
            $team->setGame($this);
        }
        return $this;
    }

    public function removeTeam(Team $team): static
    {
        if ($this->teams->removeElement($team)) {
            // set the owning side to null (unless already changed)
            if ($team->getGame() === $this) {
                $team->setGame(null);
            }
        }
        return $this;
    }
    
    /**
     * @return Collection<int, Move>
     */
    public function getMoves(): Collection
    {
        return $this->moves;
    }

    public function addMove(Move $move): static
    {
        if (!$this->moves->contains($move)) {
            $this->moves->add($move);
            $move->setGame($this);
        }
        return $this;
    }

    public function getWinner(): ?Team
    {
        return $this->winner;
    }

    public function setWinner(?Team $winner): static
    {
        $this->winner = $winner;
        return $this;
    }

    // Méthodes utilitaires
    public function getTeamByColor(TeamColor $color): ?Team
    {
        foreach ($this->teams as $team) {
            if ($team->getColor() === $color) {
                return $team;
            }
        }
        return null;
    }

    public function switchTurn(): void
    {
        $this->currentTurn = ($this->currentTurn === TeamColor::WHITE) ? TeamColor::BLACK : TeamColor::WHITE;
    }
}
