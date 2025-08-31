<?php

namespace App\Entity;

use App\Infrastructure\Doctrine\Repository\InviteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InviteRepository::class)]
#[ORM\Index(columns: ['code'], name: 'idx_invite_code')]
class Invite
{
    #[ORM\Id, ORM\Column(type: 'uuid', unique: true)]
    private ?string $id = null;

    #[ORM\OneToOne(inversedBy: 'invite', targetEntity: Game::class)]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private Game $game;

    #[ORM\Column(length: 16, unique: true)]
    private string $code;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    public function __construct(Game $game, string $code)
    {
        $this->id = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->game = $game;
        $this->code = $code;
        $this->expiresAt = new \DateTimeImmutable('+7 days');
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function setGame(Game $g): self
    {
        $this->game = $g;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $c): self
    {
        $this->code = $c;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $dt): self
    {
        $this->expiresAt = $dt;

        return $this;
    }
}
