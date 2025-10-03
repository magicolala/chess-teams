<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '"user"')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Please enter an email')]
    #[Assert\Email(message: 'The email "{{ value }}" is not a valid email.')]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Assert\Length(min: 6, max: 255, minMessage: 'Your password must be at least {{ limit }} characters long')]
    private ?string $password = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\NotBlank(message: 'Please enter a display name')]
    #[Assert\Length(min: 3, max: 50, minMessage: 'Your display name must be at least {{ limit }} characters long')]
    private ?string $displayName = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function __construct()
    {
        $this->id = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    #[\Deprecated(message: 'User::eraseCredentials() is deprecated because the entity does not store temporary sensitive data; remove this override once upgrading to Symfony 8 where the method disappears.', since: '2.1.0')]
    public function eraseCredentials(): void
    {
        // @deprecated User::eraseCredentials() kept for Symfony < 8 compatibility; remove when the interface drops it.
    }

    /**
     * Get the value of createdAt.
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set the value of createdAt.
     *
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get the value of displayName.
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Set the value of displayName.
     *
     * @return self
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;

        return $this;
    }
}
