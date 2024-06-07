<?php

namespace App\Tests\Factory\Publication\Dossier\Type\AnnualReport;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Tests\Factory\OrganisationFactory;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<AnnualReport>
 *
 * @method        AnnualReport|Proxy               create(array|callable $attributes = [])
 * @method static AnnualReport|Proxy               createOne(array $attributes = [])
 * @method static AnnualReport|Proxy               find(object|array|mixed $criteria)
 * @method static AnnualReport|Proxy               findOrCreate(array $attributes)
 * @method static AnnualReport|Proxy               first(string $sortedField = 'id')
 * @method static AnnualReport|Proxy               last(string $sortedField = 'id')
 * @method static AnnualReport|Proxy               random(array $attributes = [])
 * @method static AnnualReport|Proxy               randomOrCreate(array $attributes = [])
 * @method static EntityRepository|RepositoryProxy repository()
 * @method static AnnualReport[]|Proxy[]           all()
 * @method static AnnualReport[]|Proxy[]           createMany(int $number, array|callable $attributes = [])
 * @method static AnnualReport[]|Proxy[]           createSequence(iterable|callable $sequence)
 * @method static AnnualReport[]|Proxy[]           findBy(array $attributes)
 * @method static AnnualReport[]|Proxy[]           randomRange(int $min, int $max, array $attributes = [])
 * @method static AnnualReport[]|Proxy[]           randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<AnnualReport> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<AnnualReport> createOne(array $attributes = [])
 * @phpstan-method static Proxy<AnnualReport> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<AnnualReport> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<AnnualReport> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<AnnualReport> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<AnnualReport> random(array $attributes = [])
 * @phpstan-method static Proxy<AnnualReport> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<AnnualReport> repository()
 * @phpstan-method static list<Proxy<AnnualReport>> all()
 * @phpstan-method static list<Proxy<AnnualReport>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<AnnualReport>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<AnnualReport>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<AnnualReport>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<AnnualReport>> randomSet(int $number, array $attributes = [])
 */
final class AnnualReportFactory extends ModelFactory
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
        return AnnualReport::class;
    }
}
