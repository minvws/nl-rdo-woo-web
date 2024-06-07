<?php

namespace App\Tests\Factory;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Roles;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<User>
 *
 * @method        User|Proxy                     create(array|callable $attributes = [])
 * @method static User|Proxy                     createOne(array $attributes = [])
 * @method static User|Proxy                     find(object|array|mixed $criteria)
 * @method static User|Proxy                     findOrCreate(array $attributes)
 * @method static User|Proxy                     first(string $sortedField = 'id')
 * @method static User|Proxy                     last(string $sortedField = 'id')
 * @method static User|Proxy                     random(array $attributes = [])
 * @method static User|Proxy                     randomOrCreate(array $attributes = [])
 * @method static UserRepository|RepositoryProxy repository()
 * @method static User[]|Proxy[]                 all()
 * @method static User[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static User[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static User[]|Proxy[]                 findBy(array $attributes)
 * @method static User[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static User[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<User> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<User> createOne(array $attributes = [])
 * @phpstan-method static Proxy<User> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<User> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<User> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<User> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<User> random(array $attributes = [])
 * @phpstan-method static Proxy<User> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<User> repository()
 * @phpstan-method static list<Proxy<User>> all()
 * @phpstan-method static list<Proxy<User>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<User>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<User>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<User>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<User>> randomSet(int $number, array $attributes = [])
 */
final class UserFactory extends ModelFactory
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
        return $this->addState([
            'roles' => [Roles::ROLE_SUPER_ADMIN],
        ]);
    }

    public function isEnabled(): self
    {
        return $this->addState([
            'enabled' => true,
            'changepwd' => false,
        ]);
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function getDefaults(): array
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
    protected function initialize(): self
    {
        return $this
            ->afterInstantiate(function (User $user): void {
                $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
            })
        ;
    }

    protected static function getClass(): string
    {
        return User::class;
    }
}
