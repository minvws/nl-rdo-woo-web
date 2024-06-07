<?php

declare(strict_types=1);

namespace App\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use App\Tests\Factory\OrganisationFactory;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<ComplaintJudgement>
 *
 * @method        ComplaintJudgement|Proxy         create(array|callable $attributes = [])
 * @method static ComplaintJudgement|Proxy         createOne(array $attributes = [])
 * @method static ComplaintJudgement|Proxy         find(object|array|mixed $criteria)
 * @method static ComplaintJudgement|Proxy         findOrCreate(array $attributes)
 * @method static ComplaintJudgement|Proxy         first(string $sortedField = 'id')
 * @method static ComplaintJudgement|Proxy         last(string $sortedField = 'id')
 * @method static ComplaintJudgement|Proxy         random(array $attributes = [])
 * @method static ComplaintJudgement|Proxy         randomOrCreate(array $attributes = [])
 * @method static EntityRepository|RepositoryProxy repository()
 * @method static ComplaintJudgement[]|Proxy[]     all()
 * @method static ComplaintJudgement[]|Proxy[]     createMany(int $number, array|callable $attributes = [])
 * @method static ComplaintJudgement[]|Proxy[]     createSequence(iterable|callable $sequence)
 * @method static ComplaintJudgement[]|Proxy[]     findBy(array $attributes)
 * @method static ComplaintJudgement[]|Proxy[]     randomRange(int $min, int $max, array $attributes = [])
 * @method static ComplaintJudgement[]|Proxy[]     randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<ComplaintJudgement> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<ComplaintJudgement> createOne(array $attributes = [])
 * @phpstan-method static Proxy<ComplaintJudgement> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<ComplaintJudgement> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<ComplaintJudgement> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<ComplaintJudgement> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<ComplaintJudgement> random(array $attributes = [])
 * @phpstan-method static Proxy<ComplaintJudgement> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<ComplaintJudgement> repository()
 * @phpstan-method static list<Proxy<ComplaintJudgement>> all()
 * @phpstan-method static list<Proxy<ComplaintJudgement>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<ComplaintJudgement>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<ComplaintJudgement>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<ComplaintJudgement>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<ComplaintJudgement>> randomSet(int $number, array $attributes = [])
 */
final class ComplaintJudgementFactory extends ModelFactory
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
        return ComplaintJudgement::class;
    }
}
