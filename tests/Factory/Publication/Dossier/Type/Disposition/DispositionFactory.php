<?php

declare(strict_types=1);

namespace App\Tests\Factory\Publication\Dossier\Type\Disposition;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\Disposition\Disposition;
use App\Tests\Factory\OrganisationFactory;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Disposition>
 *
 * @method        Disposition|Proxy                create(array|callable $attributes = [])
 * @method static Disposition|Proxy                createOne(array $attributes = [])
 * @method static Disposition|Proxy                find(object|array|mixed $criteria)
 * @method static Disposition|Proxy                findOrCreate(array $attributes)
 * @method static Disposition|Proxy                first(string $sortedField = 'id')
 * @method static Disposition|Proxy                last(string $sortedField = 'id')
 * @method static Disposition|Proxy                random(array $attributes = [])
 * @method static Disposition|Proxy                randomOrCreate(array $attributes = [])
 * @method static EntityRepository|RepositoryProxy repository()
 * @method static Disposition[]|Proxy[]            all()
 * @method static Disposition[]|Proxy[]            createMany(int $number, array|callable $attributes = [])
 * @method static Disposition[]|Proxy[]            createSequence(iterable|callable $sequence)
 * @method static Disposition[]|Proxy[]            findBy(array $attributes)
 * @method static Disposition[]|Proxy[]            randomRange(int $min, int $max, array $attributes = [])
 * @method static Disposition[]|Proxy[]            randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<Disposition> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<Disposition> createOne(array $attributes = [])
 * @phpstan-method static Proxy<Disposition> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<Disposition> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<Disposition> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<Disposition> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<Disposition> random(array $attributes = [])
 * @phpstan-method static Proxy<Disposition> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<Disposition> repository()
 * @phpstan-method static list<Proxy<Disposition>> all()
 * @phpstan-method static list<Proxy<Disposition>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<Disposition>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<Disposition>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<Disposition>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<Disposition>> randomSet(int $number, array $attributes = [])
 */
final class DispositionFactory extends ModelFactory
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
        return Disposition::class;
    }
}
