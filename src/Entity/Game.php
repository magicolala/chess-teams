<?php

namespace App\Entity;

use App\Infrastructure\Doctrine\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    public const STATUS_LOBBY = 'lobby';
    public const STATUS_WAITING = 'waiting';
    public const STATUS_LIVE = 'live';
    public const STATUS_DONE = 'done';

    public const TEAM_A = 'A';
    public const TEAM_B = 'B';

    public const STATUS_FINISHED = 'finished';

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

    // Gestion du mode rapide (chrono de 1 minute)
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $fastModeEnabled = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $fastModeDeadline = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $result = null; // Ex: 'A#' (A mat), 'B#', '1/2-1/2', 'resignA', 'timeoutA', etc.

    // Compteur de timeouts consécutifs pour la revendication de victoire
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $consecutiveTimeouts = 0;

    #[ORM\Column(length: 1, nullable: true)]
    private ?string $lastTimeoutTeam = null; // 'A' ou 'B' - pour tracker les timeouts consécutifs

    #[ORM\OneToOne(mappedBy: 'game', targetEntity: Invite::class, cascade: ['persist', 'remove'])]
    private ?Invite $invite = null;

    #[ORM\OneToMany(mappedBy: 'game', targetEntity: Team::class, orphanRemoval: true)]
    private Collection $teams;

    public function __construct()
    {
        // UUID en texte (SQLite-friendly)
        $this->id = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
        $this->teams = new ArrayCollection();
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

    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setResult(?string $r): self
    {
        $this->result = $r;

        return $this;
    }

    public function isFastModeEnabled(): bool
    {
        return $this->fastModeEnabled;
    }

    public function setFastModeEnabled(bool $enabled): self
    {
        $this->fastModeEnabled = $enabled;

        return $this;
    }

    public function getFastModeDeadline(): ?\DateTimeImmutable
    {
        return $this->fastModeDeadline;
    }

    public function setFastModeDeadline(?\DateTimeImmutable $deadline): self
    {
        $this->fastModeDeadline = $deadline;

        return $this;
    }

    /**
     * Retourne le délai effectif selon le mode actuel
     * (fastModeDeadline si mode rapide activé, sinon turnDeadline).
     */
    public function getEffectiveDeadline(): ?\DateTimeImmutable
    {
        if ($this->fastModeEnabled && $this->fastModeDeadline) {
            return $this->fastModeDeadline;
        }

        return $this->turnDeadline;
    }

    public function getInvite(): ?Invite
    {
        return $this->invite;
    }

    public function setInvite(Invite $invite): self
    {
        // S'assure que l'autre côté de la relation est bien défini
        if ($invite->getGame() !== $this) {
            $invite->setGame($this);
        }

        $this->invite = $invite;

        return $this;
    }

    public function getConsecutiveTimeouts(): int
    {
        return $this->consecutiveTimeouts;
    }

    public function setConsecutiveTimeouts(int $count): self
    {
        $this->consecutiveTimeouts = $count;

        return $this;
    }

    public function incrementConsecutiveTimeouts(): self
    {
        ++$this->consecutiveTimeouts;

        return $this;
    }

    public function resetConsecutiveTimeouts(): self
    {
        $this->consecutiveTimeouts = 0;
        $this->lastTimeoutTeam = null;

        return $this;
    }

    public function getLastTimeoutTeam(): ?string
    {
        return $this->lastTimeoutTeam;
    }

    public function setLastTimeoutTeam(?string $team): self
    {
        $this->lastTimeoutTeam = $team;

        return $this;
    }

    /**
     * Vérifie si l'adversaire peut revendiquer la victoire
     * (après 3 timeouts consécutifs de la même équipe).
     */
    public function canClaimVictory(): bool
    {
        return $this->consecutiveTimeouts >= 3 && null !== $this->lastTimeoutTeam;
    }

    /**
     * Détermine quelle équipe peut revendiquer la victoire.
     */
    public function getClaimVictoryTeam(): ?string
    {
        if (!$this->canClaimVictory()) {
            return null;
        }

        return self::TEAM_A === $this->lastTimeoutTeam ? self::TEAM_B : self::TEAM_A;
    }

    /**
     * @return Collection<int, Team>
     */
    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function addTeam(Team $team): self
    {
        if (!$this->teams->contains($team)) {
            $this->teams[] = $team;
        }

        return $this;
    }

    public function removeTeam(Team $team): self
    {
        $this->teams->removeElement($team);

        return $this;
    }

    /**
     * Récupère l'équipe par son nom.
     */
    public function getTeamByName(string $name): ?Team
    {
        foreach ($this->teams as $team) {
            if ($team->getName() === $name) {
                return $team;
            }
        }

        return null;
    }

    /**
     * Récupère le TeamMember actuel dont c'est le tour de jouer.
     */
    public function getCurrentMembership(): ?TeamMember
    {
        $currentTeam = $this->getTeamByName($this->turnTeam);

        if (!$currentTeam) {
            return null;
        }

        // Récupérer les membres actifs de l'équipe triés par position
        $activeMembers = $currentTeam->getMembers()->filter(
            fn (TeamMember $member) => $member->isActive()
        )->toArray();

        // Trier par position
        usort($activeMembers, fn (TeamMember $a, TeamMember $b) => $a->getPosition() <=> $b->getPosition());

        if (empty($activeMembers)) {
            return null;
        }

        // Utiliser l'index actuel de l'équipe pour déterminer quel membre c'est le tour
        $currentIndex = $currentTeam->getCurrentIndex();
        $memberIndex = $currentIndex % count($activeMembers);

        return $activeMembers[$memberIndex] ?? null;
    }
}
