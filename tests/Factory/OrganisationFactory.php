<?php

namespace App\Tests\Factory;

use App\Entity\Organisation;

/**
 * @method        \App\Entity\Organisation|\Zenstruck\Foundry\Persistence\Proxy                                                             create(array|callable $attributes = [])
 * @method static \App\Entity\Organisation|\Zenstruck\Foundry\Persistence\Proxy                                                             createOne(array $attributes = [])
 * @method static \App\Entity\Organisation|\Zenstruck\Foundry\Persistence\Proxy                                                             find(object|array|mixed $criteria)
 * @method static \App\Entity\Organisation|\Zenstruck\Foundry\Persistence\Proxy                                                             findOrCreate(array $attributes)
 * @method static \App\Entity\Organisation|\Zenstruck\Foundry\Persistence\Proxy                                                             first(string $sortedField = 'id')
 * @method static \App\Entity\Organisation|\Zenstruck\Foundry\Persistence\Proxy                                                             last(string $sortedField = 'id')
 * @method static \App\Entity\Organisation|\Zenstruck\Foundry\Persistence\Proxy                                                             random(array $attributes = [])
 * @method static \App\Entity\Organisation|\Zenstruck\Foundry\Persistence\Proxy                                                             randomOrCreate(array $attributes = [])
 * @method static \App\Entity\Organisation[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                         all()
 * @method static \App\Entity\Organisation[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                         createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\Organisation[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                         createSequence(iterable|callable $sequence)
 * @method static \App\Entity\Organisation[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                         findBy(array $attributes)
 * @method static \App\Entity\Organisation[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                         randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\Organisation[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                         randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Organisation|\Zenstruck\Foundry\Persistence\Proxy>                       many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Organisation|\Zenstruck\Foundry\Persistence\Proxy>                       sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Entity\Organisation, \App\Repository\OrganisationRepository> repository()
 *
 * @phpstan-method \App\Entity\Organisation&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Organisation> create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\Organisation&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Organisation> createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\Organisation&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Organisation> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\Organisation&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Organisation> findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\Organisation&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Organisation> first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Organisation&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Organisation> last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Organisation&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Organisation> random(array $attributes = [])
 * @phpstan-method static \App\Entity\Organisation&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Organisation> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\Organisation&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Organisation>> all()
 * @phpstan-method static list<\App\Entity\Organisation&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Organisation>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\Organisation&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Organisation>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\Organisation&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Organisation>> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\Organisation&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Organisation>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\Organisation&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Organisation>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Organisation&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Organisation>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Organisation&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Organisation>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Entity\Organisation>
 */
final class OrganisationFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'departments' => [DepartmentFactory::random()],
            'name' => self::faker()->text(255),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Organisation $organisation): void {})
        ;
    }

    public static function class(): string
    {
        return Organisation::class;
    }
}
