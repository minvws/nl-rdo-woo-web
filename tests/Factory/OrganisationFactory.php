<?php

namespace App\Tests\Factory;

use App\Entity\Organisation;
use App\Repository\OrganisationRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Organisation>
 *
 * @method        Organisation|Proxy                     create(array|callable $attributes = [])
 * @method static Organisation|Proxy                     createOne(array $attributes = [])
 * @method static Organisation|Proxy                     find(object|array|mixed $criteria)
 * @method static Organisation|Proxy                     findOrCreate(array $attributes)
 * @method static Organisation|Proxy                     first(string $sortedField = 'id')
 * @method static Organisation|Proxy                     last(string $sortedField = 'id')
 * @method static Organisation|Proxy                     random(array $attributes = [])
 * @method static Organisation|Proxy                     randomOrCreate(array $attributes = [])
 * @method static OrganisationRepository|RepositoryProxy repository()
 * @method static Organisation[]|Proxy[]                 all()
 * @method static Organisation[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Organisation[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static Organisation[]|Proxy[]                 findBy(array $attributes)
 * @method static Organisation[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static Organisation[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<Organisation> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<Organisation> createOne(array $attributes = [])
 * @phpstan-method static Proxy<Organisation> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<Organisation> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<Organisation> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<Organisation> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<Organisation> random(array $attributes = [])
 * @phpstan-method static Proxy<Organisation> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<Organisation> repository()
 * @phpstan-method static list<Proxy<Organisation>> all()
 * @phpstan-method static list<Proxy<Organisation>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<Organisation>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<Organisation>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<Organisation>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<Organisation>> randomSet(int $number, array $attributes = [])
 */
final class OrganisationFactory extends ModelFactory
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
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'department' => DepartmentFactory::new(),
            'name' => self::faker()->text(255),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(Organisation $organisation): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Organisation::class;
    }
}
