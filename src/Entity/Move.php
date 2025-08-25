<?php

namespace App\Entity;

use App\Repository\MoveRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MoveRepository::class)]
#[ORM\Index(columns: ['game_id', 'ply'])]
class Move
{
    public const TYPE_NORMAL  = 'normal';
    public const TYPE_TIMEOUT = 'timeout-pass';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Game $game;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    private ?Team $team = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $byUser = null;

    #[ORM\Column(type: 'integer')]
    private int $ply;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $uci = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $san = null;

    #[ORM\Column(type: 'text')]
    private string $fenAfter;

    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_NORMAL;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Game $g, int $ply)
    {
        $this->id        = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->game      = $g;
        $this->ply       = $ply;
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters/Setters
    public function getId(): string
    {
        return $this->id;
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $t): self
    {
        $this->team = $t;

        return $this;
    }

    public function getByUser(): ?User
    {
        return $this->byUser;
    }

    public function setByUser(?User $u): self
    {
        $this->byUser = $u;

        return $this;
    }

    public function getPly(): int
    {
        return $this->ply;
    }

    public function getUci(): ?string
    {
        return $this->uci;
    }

    public function setUci(?string $u): self
    {
        $this->uci = $u;

        return $this;
    }

    public function getSan(): ?string
    {
        return $this->san;
    }

    public function setSan(?string $s): self
    {
        $this->san = $s;

        return $this;
    }

    public function getFenAfter(): string
    {
        return $this->fenAfter;
    }

    public function setFenAfter(string $f): self
    {
        $this->fenAfter = $f;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $t): self
    {
        $this->type = $t;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
