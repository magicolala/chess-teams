<?php

namespace App\Entity;

use App\Infrastructure\Doctrine\Repository\GameRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    public const STATUS_LOBBY = 'lobby';
    public const STATUS_LIVE = 'live';
    public const STATUS_DONE = 'done';

    public const TEAM_A = 'A';
    public const TEAM_B = 'B';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(length: 10)]
    private string $status = self::STATUS_LOBBY;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $createdBy = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    // options
    #[ORM\Column(type: 'integer')]
    private int $turnDurationSec = 60;

    #[ORM\Column(length: 16)]
    private string $visibility = 'private';

    // état
    #[ORM\Column(type: 'text')]
    private string $fen = 'startpos';

    #[ORM\Column(type: 'integer')]
    private int $ply = 0;

    #[ORM\Column(length: 1)]
    private string $turnTeam = self::TEAM_A;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $turnDeadline = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $result = null;

    public function __construct()
    {
        // UUID en texte (SQLite-friendly)
        $this->id = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters/Setters
    public function getId(): string
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $s): self
    {
        $this->status = $s;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $u): self
    {
        $this->createdBy = $u;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $d): self
    {
        $this->updatedAt = $d;

        return $this;
    }

    public function getTurnDurationSec(): int
    {
        return $this->turnDurationSec;
    }

    public function setTurnDurationSec(int $s): self
    {
        $this->turnDurationSec = $s;

        return $this;
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function setVisibility(string $v): self
    {
        $this->visibility = $v;

        return $this;
    }

    public function getFen(): string
    {
        return $this->fen;
    }

    public function setFen(string $f): self
    {
        $this->fen = $f;

        return $this;
    }

    public function getPly(): int
    {
        return $this->ply;
    }

    public function setPly(int $p): self
    {
        $this->ply = $p;

        return $this;
    }

    public function getTurnTeam(): string
    {
        return $this->turnTeam;
    }

    public function setTurnTeam(string $t): self
    {
        $this->turnTeam = $t;

        return $this;
    }

    public function getTurnDeadline(): ?\DateTimeImmutable
    {
        return $this->turnDeadline;
    }

    public function setTurnDeadline(?\DateTimeImmutable $d): self
    {
        $this->turnDeadline = $d;

        return $this;
    }

    public function getResult(): ?array
    {
        return $this->result;
    }

    public function setResult(?array $r): self
    {
        $this->result = $r;

        return $this;
    }
}
