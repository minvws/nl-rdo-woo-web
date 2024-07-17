<?php

declare(strict_types=1);

namespace App\Tests\Factory\Publication\Dossier\Type\Disposition;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\Disposition\Disposition;
use App\Tests\Factory\OrganisationFactory;
use Carbon\CarbonImmutable;

/**
 * @method        \App\Domain\Publication\Dossier\Type\Disposition\Disposition|\Zenstruck\Foundry\Persistence\Proxy                                                                                             create(array|callable $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\Disposition|\Zenstruck\Foundry\Persistence\Proxy                                                                                             createOne(array $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\Disposition|\Zenstruck\Foundry\Persistence\Proxy                                                                                             find(object|array|mixed $criteria)
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\Disposition|\Zenstruck\Foundry\Persistence\Proxy                                                                                             findOrCreate(array $attributes)
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\Disposition|\Zenstruck\Foundry\Persistence\Proxy                                                                                             first(string $sortedField = 'id')
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\Disposition|\Zenstruck\Foundry\Persistence\Proxy                                                                                             last(string $sortedField = 'id')
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\Disposition|\Zenstruck\Foundry\Persistence\Proxy                                                                                             random(array $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\Disposition|\Zenstruck\Foundry\Persistence\Proxy                                                                                             randomOrCreate(array $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\Disposition[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                         all()
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\Disposition[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                         createMany(int $number, array|callable $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\Disposition[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                         createSequence(iterable|callable $sequence)
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\Disposition[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                         findBy(array $attributes)
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\Disposition[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                         randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\Disposition\Disposition[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                                                         randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Domain\Publication\Dossier\Type\Disposition\Disposition|\Zenstruck\Foundry\Persistence\Proxy>                                                       many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Domain\Publication\Dossier\Type\Disposition\Disposition|\Zenstruck\Foundry\Persistence\Proxy>                                                       sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Domain\Publication\Dossier\Type\Disposition\Disposition, \App\Domain\Publication\Dossier\Type\Disposition\DispositionRepository> repository()
 *
 * @phpstan-method \App\Domain\Publication\Dossier\Type\Disposition\Disposition&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\Disposition> create(array|callable $attributes = [])
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\Disposition\Disposition&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\Disposition> createOne(array $attributes = [])
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\Disposition\Disposition&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\Disposition> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\Disposition\Disposition&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\Disposition> findOrCreate(array $attributes)
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\Disposition\Disposition&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\Disposition> first(string $sortedField = 'id')
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\Disposition\Disposition&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\Disposition> last(string $sortedField = 'id')
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\Disposition\Disposition&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\Disposition> random(array $attributes = [])
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\Disposition\Disposition&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\Disposition> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\Disposition\Disposition&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\Disposition>> all()
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\Disposition\Disposition&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\Disposition>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\Disposition\Disposition&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\Disposition>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\Disposition\Disposition&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\Disposition>> findBy(array $attributes)
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\Disposition\Disposition&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\Disposition>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\Disposition\Disposition&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\Disposition>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Domain\Publication\Dossier\Type\Disposition\Disposition&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\Disposition>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Domain\Publication\Dossier\Type\Disposition\Disposition&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\Disposition\Disposition>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Domain\Publication\Dossier\Type\Disposition\Disposition>
 */
final class DispositionFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
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
        return Disposition::class;
    }
}
