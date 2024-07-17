<?php

namespace App\Tests\Factory;

use App\Entity\User;
use App\Roles;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @method        \App\Entity\User|\Zenstruck\Foundry\Persistence\Proxy                                                     create(array|callable $attributes = [])
 * @method static \App\Entity\User|\Zenstruck\Foundry\Persistence\Proxy                                                     createOne(array $attributes = [])
 * @method static \App\Entity\User|\Zenstruck\Foundry\Persistence\Proxy                                                     find(object|array|mixed $criteria)
 * @method static \App\Entity\User|\Zenstruck\Foundry\Persistence\Proxy                                                     findOrCreate(array $attributes)
 * @method static \App\Entity\User|\Zenstruck\Foundry\Persistence\Proxy                                                     first(string $sortedField = 'id')
 * @method static \App\Entity\User|\Zenstruck\Foundry\Persistence\Proxy                                                     last(string $sortedField = 'id')
 * @method static \App\Entity\User|\Zenstruck\Foundry\Persistence\Proxy                                                     random(array $attributes = [])
 * @method static \App\Entity\User|\Zenstruck\Foundry\Persistence\Proxy                                                     randomOrCreate(array $attributes = [])
 * @method static \App\Entity\User[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 all()
 * @method static \App\Entity\User[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\User[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createSequence(iterable|callable $sequence)
 * @method static \App\Entity\User[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 findBy(array $attributes)
 * @method static \App\Entity\User[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\User[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\User|\Zenstruck\Foundry\Persistence\Proxy>               many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\User|\Zenstruck\Foundry\Persistence\Proxy>               sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Entity\User, \App\Repository\UserRepository> repository()
 *
 * @phpstan-method \App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User> create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User> createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User> findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User> first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User> last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User> random(array $attributes = [])
 * @phpstan-method static \App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User>> all()
 * @phpstan-method static list<\App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User>> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\User&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\User>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Entity\User>
 */
final class UserFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
    }

    public function asAdmin(): self
    {
        return $this->with([
            'roles' => [Roles::ROLE_SUPER_ADMIN],
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
