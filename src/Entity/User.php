<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use MinVWS\AuditLogger\Contracts\LoggableUser;
use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface, BackupCodeInterface, LoggableUser
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    /** @var string[] */
    #[ORM\Column(type: Types::JSON, options: ['jsonb' => true])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private string $password;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'encrypted_string', nullable: true)]
    private ?string $mfaToken = null;

    /**
     * @var string[]|null
     */
    #[ORM\Column(type: 'encrypted_array', nullable: true)]
    private ?array $mfaRecovery = null;

    #[ORM\Column]
    private bool $enabled;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column]
    private bool $changepwd;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private Organisation $organisation;

    /** @var Collection|LoginActivity[] */
    #[ORM\OneToMany(mappedBy: 'account', targetEntity: LoginActivity::class, orphanRemoval: true)]
    private Collection $loginActivities;

    public function __construct()
    {
        $this->loginActivities = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
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
     * @return string[]
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        return array_unique($roles);
    }

    public function hasRole(string $role): bool
    {
        return in_array(strtoupper($role), $this->roles);
    }

    /**
     * @param string[] $roles
     *
     * @return $this
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getMfaToken(): ?string
    {
        return $this->mfaToken;
    }

    public function setMfaToken(?string $mfaToken): self
    {
        $this->mfaToken = $mfaToken;

        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getMfaRecovery(): ?array
    {
        return $this->mfaRecovery;
    }

    /**
     * @param string[]|null $mfaRecovery
     *
     * @return $this
     */
    public function setMfaRecovery(?array $mfaRecovery): static
    {
        $this->mfaRecovery = $mfaRecovery;

        return $this;
    }

    public function isTotpAuthenticationEnabled(): bool
    {
        return true;
    }

    public function getTotpAuthenticationUsername(): string
    {
        return $this->email;
    }

    public function getTotpAuthenticationConfiguration(): TotpConfigurationInterface
    {
        return new TotpConfiguration($this->mfaToken ?? '', TotpConfiguration::ALGORITHM_SHA1, 30, 6);
    }

    public function isBackupCode(string $code): bool
    {
        return in_array($code, $this->mfaRecovery ?? []);
    }

    public function invalidateBackupCode(string $code): void
    {
        $this->mfaRecovery = array_filter($this->mfaRecovery ?? [], fn ($target) => $target !== $code);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function isPasswordChangeRequired(): bool
    {
        return $this->changepwd == true;
    }

    public function setChangepwd(bool $changepwd): self
    {
        $this->changepwd = $changepwd;

        return $this;
    }

    public function isChangepwd(): ?bool
    {
        return $this->changepwd;
    }

    public function getAuditId(): string
    {
        return (string) $this->getId();
    }

    public function getOrganisation(): Organisation
    {
        return $this->organisation;
    }

    public function setOrganisation(Organisation $organisation): static
    {
        $this->organisation = $organisation;

        return $this;
    }

    /**
     * @return Collection<array-key,LoginActivity>
     */
    public function getLoginActivities(): Collection
    {
        return $this->loginActivities;
    }

    public function addLoginActivity(LoginActivity $loginActivity): static
    {
        if (! $this->loginActivities->contains($loginActivity)) {
            $this->loginActivities->add($loginActivity);
            $loginActivity->setAccount($this);
        }

        return $this;
    }

    public function removeLoginActivity(LoginActivity $loginActivity): static
    {
        $this->loginActivities->removeElement($loginActivity);

        return $this;
    }
}
