<?php

declare(strict_types=1);

namespace App\Tests\Factory\Publication\Dossier\Type\InvestigationReport;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use App\Tests\Factory\OrganisationFactory;
use Carbon\CarbonImmutable;

/**
 * @method        \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport|\Zenstruck\Foundry\Persistence\Proxy                                                                                                             create(array|callable $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport|\Zenstruck\Foundry\Persistence\Proxy                                                                                                             createOne(array $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport|\Zenstruck\Foundry\Persistence\Proxy                                                                                                             find(object|array|mixed $criteria)
 * @method static \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport|\Zenstruck\Foundry\Persistence\Proxy                                                                                                             findOrCreate(array $attributes)
 * @method static \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport|\Zenstruck\Foundry\Persistence\Proxy                                                                                                             first(string $sortedField = 'id')
 * @method static \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport|\Zenstruck\Foundry\Persistence\Proxy                                                                                                             last(string $sortedField = 'id')
 * @method static \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport|\Zenstruck\Foundry\Persistence\Proxy                                                                                                             random(array $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport|\Zenstruck\Foundry\Persistence\Proxy                                                                                                             randomOrCreate(array $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                                         all()
 * @method static \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                                         createMany(int $number, array|callable $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                                         createSequence(iterable|callable $sequence)
 * @method static \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                                         findBy(array $attributes)
 * @method static \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                                         randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                                         randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport|\Zenstruck\Foundry\Persistence\Proxy>                                                                       many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport|\Zenstruck\Foundry\Persistence\Proxy>                                                                       sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport, \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportRepository> repository()
 *
 * @phpstan-method \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport> create(array|callable $attributes = [])
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport> createOne(array $attributes = [])
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport> findOrCreate(array $attributes)
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport> first(string $sortedField = 'id')
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport> last(string $sortedField = 'id')
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport> random(array $attributes = [])
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport>> all()
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport>> findBy(array $attributes)
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport>
 */
final class InvestigationReportFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
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

    public static function class(): string
    {
        return InvestigationReport::class;
    }
}
