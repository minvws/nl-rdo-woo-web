<?php

declare(strict_types=1);

namespace Shared\Tests\Factory;

use Shared\Service\Security\Roles;
use Shared\Service\Security\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
final class UserFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     */
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    public function asSuperAdmin(): self
    {
        return $this->with([
            'roles' => [Roles::ROLE_SUPER_ADMIN],
        ]);
    }

    public function asOrganisationAdmin(): self
    {
        return $this->with([
            'roles' => [Roles::ROLE_ORGANISATION_ADMIN],
        ]);
    }

    public function asDossierAdmin(): self
    {
        return $this->with([
            'roles' => [Roles::ROLE_DOSSIER_ADMIN],
        ]);
    }

    public function asViewAccess(): self
    {
        return $this->with([
            'roles' => [Roles::ROLE_VIEW_ACCESS],
        ]);
    }

    public function isEnabled(): self
    {
        return $this->with([
            'enabled' => true,
            'changepwd' => false,
        ]);
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'changepwd' => self::faker()->boolean(),
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'email' => sprintf('%s@example.local', self::faker()->uuid()),
            'enabled' => self::faker()->boolean(),
            'name' => self::faker()->name(),
            'organisation' => OrganisationFactory::new(),
            'password' => self::faker()->words(asText: true),
            'roles' => [],
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function (User $user): void {
                $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
            })
        ;
    }

    public static function class(): string
    {
        return User::class;
    }
}
