<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class UserWerewolfStats
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $correctIdentifications = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $werewolfSuccesses = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $user)
    {
        $this->id = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->user = $user;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getCorrectIdentifications(): int { return $this->correctIdentifications; }
    public function getWerewolfSuccesses(): int { return $this->werewolfSuccesses; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public function addCorrectIdentification(): self { ++$this->correctIdentifications; $this->updatedAt = new \DateTimeImmutable(); return $this; }
    public function addWerewolfSuccess(): self { ++$this->werewolfSuccesses; $this->updatedAt = new \DateTimeImmutable(); return $this; }
}
