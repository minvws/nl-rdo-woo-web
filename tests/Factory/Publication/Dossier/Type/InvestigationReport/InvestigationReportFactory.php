<?php

declare(strict_types=1);

namespace App\Tests\Factory\Publication\Dossier\Type\InvestigationReport;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use App\Tests\Factory\OrganisationFactory;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<InvestigationReport>
 *
 * @method        InvestigationReport|Proxy        create(array|callable $attributes = [])
 * @method static InvestigationReport|Proxy        createOne(array $attributes = [])
 * @method static InvestigationReport|Proxy        find(object|array|mixed $criteria)
 * @method static InvestigationReport|Proxy        findOrCreate(array $attributes)
 * @method static InvestigationReport|Proxy        first(string $sortedField = 'id')
 * @method static InvestigationReport|Proxy        last(string $sortedField = 'id')
 * @method static InvestigationReport|Proxy        random(array $attributes = [])
 * @method static InvestigationReport|Proxy        randomOrCreate(array $attributes = [])
 * @method static EntityRepository|RepositoryProxy repository()
 * @method static InvestigationReport[]|Proxy[]    all()
 * @method static InvestigationReport[]|Proxy[]    createMany(int $number, array|callable $attributes = [])
 * @method static InvestigationReport[]|Proxy[]    createSequence(iterable|callable $sequence)
 * @method static InvestigationReport[]|Proxy[]    findBy(array $attributes)
 * @method static InvestigationReport[]|Proxy[]    randomRange(int $min, int $max, array $attributes = [])
 * @method static InvestigationReport[]|Proxy[]    randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<InvestigationReport> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<InvestigationReport> createOne(array $attributes = [])
 * @phpstan-method static Proxy<InvestigationReport> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<InvestigationReport> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<InvestigationReport> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<InvestigationReport> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<InvestigationReport> random(array $attributes = [])
 * @phpstan-method static Proxy<InvestigationReport> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<InvestigationReport> repository()
 * @phpstan-method static list<Proxy<InvestigationReport>> all()
 * @phpstan-method static list<Proxy<InvestigationReport>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<InvestigationReport>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<InvestigationReport>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<InvestigationReport>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<InvestigationReport>> randomSet(int $number, array $attributes = [])
 */
final class InvestigationReportFactory extends ModelFactory
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
        return InvestigationReport::class;
    }
}
