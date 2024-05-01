<?php

namespace App\Tests\Factory;

use App\Entity\Department;
use App\Repository\DepartmentRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Department>
 *
 * @method        Department|Proxy                     create(array|callable $attributes = [])
 * @method static Department|Proxy                     createOne(array $attributes = [])
 * @method static Department|Proxy                     find(object|array|mixed $criteria)
 * @method static Department|Proxy                     findOrCreate(array $attributes)
 * @method static Department|Proxy                     first(string $sortedField = 'id')
 * @method static Department|Proxy                     last(string $sortedField = 'id')
 * @method static Department|Proxy                     random(array $attributes = [])
 * @method static Department|Proxy                     randomOrCreate(array $attributes = [])
 * @method static DepartmentRepository|RepositoryProxy repository()
 * @method static Department[]|Proxy[]                 all()
 * @method static Department[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Department[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static Department[]|Proxy[]                 findBy(array $attributes)
 * @method static Department[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static Department[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<Department> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<Department> createOne(array $attributes = [])
 * @phpstan-method static Proxy<Department> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<Department> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<Department> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<Department> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<Department> random(array $attributes = [])
 * @phpstan-method static Proxy<Department> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<Department> repository()
 * @phpstan-method static list<Proxy<Department>> all()
 * @phpstan-method static list<Proxy<Department>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<Department>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<Department>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<Department>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<Department>> randomSet(int $number, array $attributes = [])
 */
final class DepartmentFactory extends ModelFactory
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
     * @todo add your default values here
     */
    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->unique()->words(asText: true),
            'shortTag' => self::faker()->optional()->word(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(Department $department): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Department::class;
    }
}
