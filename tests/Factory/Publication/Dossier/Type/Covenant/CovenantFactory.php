<?php

namespace App\Tests\Factory\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Tests\Factory\OrganisationFactory;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Covenant>
 *
 * @method        Covenant|Proxy                   create(array|callable $attributes = [])
 * @method static Covenant|Proxy                   createOne(array $attributes = [])
 * @method static Covenant|Proxy                   find(object|array|mixed $criteria)
 * @method static Covenant|Proxy                   findOrCreate(array $attributes)
 * @method static Covenant|Proxy                   first(string $sortedField = 'id')
 * @method static Covenant|Proxy                   last(string $sortedField = 'id')
 * @method static Covenant|Proxy                   random(array $attributes = [])
 * @method static Covenant|Proxy                   randomOrCreate(array $attributes = [])
 * @method static EntityRepository|RepositoryProxy repository()
 * @method static Covenant[]|Proxy[]               all()
 * @method static Covenant[]|Proxy[]               createMany(int $number, array|callable $attributes = [])
 * @method static Covenant[]|Proxy[]               createSequence(iterable|callable $sequence)
 * @method static Covenant[]|Proxy[]               findBy(array $attributes)
 * @method static Covenant[]|Proxy[]               randomRange(int $min, int $max, array $attributes = [])
 * @method static Covenant[]|Proxy[]               randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<Covenant> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<Covenant> createOne(array $attributes = [])
 * @phpstan-method static Proxy<Covenant> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<Covenant> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<Covenant> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<Covenant> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<Covenant> random(array $attributes = [])
 * @phpstan-method static Proxy<Covenant> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<Covenant> repository()
 * @phpstan-method static list<Proxy<Covenant>> all()
 * @phpstan-method static list<Proxy<Covenant>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<Covenant>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<Covenant>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<Covenant>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<Covenant>> randomSet(int $number, array $attributes = [])
 */
final class CovenantFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        $publicationDate = CarbonImmutable::createFromMutable(self::faker()->dateTimeBetween('01-01-2010', '01-01-2023'));

        return [
            'dossierNr' => self::faker()->bothify('DOSSIER-####-#####'),
            'title' => self::faker()->sentence(),
            'summary' => self::faker()->sentences(4, true),
            'documentPrefix' => 'PREF',
            'status' => DossierStatus::PUBLISHED,
            'organisation' => OrganisationFactory::new(),
            'publicationDate' => $publicationDate,
        ];
    }

    protected static function getClass(): string
    {
        return Covenant::class;
    }
}
