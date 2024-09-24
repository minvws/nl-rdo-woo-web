<?php

namespace App\Tests\Factory;

use App\Entity\Department;

/**
 * @method        \App\Entity\Department|\Zenstruck\Foundry\Persistence\Proxy                                                           create(array|callable $attributes = [])
 * @method static \App\Entity\Department|\Zenstruck\Foundry\Persistence\Proxy                                                           createOne(array $attributes = [])
 * @method static \App\Entity\Department|\Zenstruck\Foundry\Persistence\Proxy                                                           find(object|array|mixed $criteria)
 * @method static \App\Entity\Department|\Zenstruck\Foundry\Persistence\Proxy                                                           findOrCreate(array $attributes)
 * @method static \App\Entity\Department|\Zenstruck\Foundry\Persistence\Proxy                                                           first(string $sortedField = 'id')
 * @method static \App\Entity\Department|\Zenstruck\Foundry\Persistence\Proxy                                                           last(string $sortedField = 'id')
 * @method static \App\Entity\Department|\Zenstruck\Foundry\Persistence\Proxy                                                           random(array $attributes = [])
 * @method static \App\Entity\Department|\Zenstruck\Foundry\Persistence\Proxy                                                           randomOrCreate(array $attributes = [])
 * @method static \App\Entity\Department[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                       all()
 * @method static \App\Entity\Department[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                       createMany(int $number, array|callable $attributes = [])
 * @method static \App\Entity\Department[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                       createSequence(iterable|callable $sequence)
 * @method static \App\Entity\Department[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                       findBy(array $attributes)
 * @method static \App\Entity\Department[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                       randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Entity\Department[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                       randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Department|\Zenstruck\Foundry\Persistence\Proxy>                     many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Entity\Department|\Zenstruck\Foundry\Persistence\Proxy>                     sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Entity\Department, \App\Repository\DepartmentRepository> repository()
 *
 * @phpstan-method \App\Entity\Department&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Department> create(array|callable $attributes = [])
 * @phpstan-method static \App\Entity\Department&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Department> createOne(array $attributes = [])
 * @phpstan-method static \App\Entity\Department&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Department> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Entity\Department&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Department> findOrCreate(array $attributes)
 * @phpstan-method static \App\Entity\Department&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Department> first(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Department&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Department> last(string $sortedField = 'id')
 * @phpstan-method static \App\Entity\Department&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Department> random(array $attributes = [])
 * @phpstan-method static \App\Entity\Department&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Department> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Entity\Department&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Department>> all()
 * @phpstan-method static list<\App\Entity\Department&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Department>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Entity\Department&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Department>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Entity\Department&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Department>> findBy(array $attributes)
 * @phpstan-method static list<\App\Entity\Department&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Department>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Entity\Department&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Department>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Department&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Department>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Entity\Department&\Zenstruck\Foundry\Persistence\Proxy<\App\Entity\Department>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Entity\Department>
 */
final class DepartmentFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
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
        /** @var string $name */
        $name = self::faker()->unique()->words(nb: 6, asText: true);

        return [
            'name' => sprintf('%s %s', $name, 'Department'),
            'shortTag' => self::faker()->word(),
            'slug' => self::faker()->word(),
            'public' => true,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Department $department): void {})
        ;
    }

    public static function class(): string
    {
        return Department::class;
    }
}
