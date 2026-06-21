<?php

declare(strict_types=1);

namespace Shared\Service\Security;

use Carbon\CarbonImmutable;
use Deprecated;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use MinVWS\AuditLogger\Contracts\LoggableUser;
use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Shared\Doctrine\TimestampableTrait;
use Shared\Domain\Organisation\Organisation;
use Shared\Service\Security\LoginActivity\LoginActivity;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

use function array_filter;
use function array_unique;
use function in_array;
use function strtoupper;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface, BackupCodeInterface, LoggableUser
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    /** @var array<array-key, string> */
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
     * @var array<array-key, string>|null
     */
    #[ORM\Column(type: 'encrypted_array', nullable: true)]
    private ?array $mfaRecovery = null;

    #[ORM\Column]
    private bool $enabled;

    #[ORM\Column]
    private bool $changepwd;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private Organisation $organisation;

    /** @var Collection<array-key,LoginActivity> */
    #[ORM\OneToMany(mappedBy: 'account', targetEntity: LoginActivity::class)]
    private Collection $loginActivities;

    public function __construct()
    {
        $this->createdAt = new CarbonImmutable();
        $this->updatedAt = new CarbonImmutable();
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

    public function getUserIdentifier(): string
    {
        $email = $this->email;
        Assert::stringNotEmpty($email);

        return $email;
    }

    /**
     * @return array<array-key, string>
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
     * @param array<array-key, string> $roles
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
    #[Deprecated]
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
     * @return array<array-key, string>|null
     */
    public function getMfaRecovery(): ?array
    {
        return $this->mfaRecovery;
    }

    /**
     * @param array<array-key, string>|null $mfaRecovery
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
        $this->mfaRecovery = array_filter($this->mfaRecovery ?? [], static fn ($target) => $target !== $code);
    }

    public function isDisabled(): bool
    {
        return ! $this->enabled;
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
