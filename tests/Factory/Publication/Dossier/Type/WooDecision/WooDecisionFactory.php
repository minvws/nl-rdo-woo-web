<?php

namespace App\Tests\Factory\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Tests\Factory\OrganisationFactory;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<WooDecision>
 *
 * @method        WooDecision|Proxy                create(array|callable $attributes = [])
 * @method static WooDecision|Proxy                createOne(array $attributes = [])
 * @method static WooDecision|Proxy                find(object|array|mixed $criteria)
 * @method static WooDecision|Proxy                findOrCreate(array $attributes)
 * @method static WooDecision|Proxy                first(string $sortedField = 'id')
 * @method static WooDecision|Proxy                last(string $sortedField = 'id')
 * @method static WooDecision|Proxy                random(array $attributes = [])
 * @method static WooDecision|Proxy                randomOrCreate(array $attributes = [])
 * @method static EntityRepository|RepositoryProxy repository()
 * @method static WooDecision[]|Proxy[]            all()
 * @method static WooDecision[]|Proxy[]            createMany(int $number, array|callable $attributes = [])
 * @method static WooDecision[]|Proxy[]            createSequence(iterable|callable $sequence)
 * @method static WooDecision[]|Proxy[]            findBy(array $attributes)
 * @method static WooDecision[]|Proxy[]            randomRange(int $min, int $max, array $attributes = [])
 * @method static WooDecision[]|Proxy[]            randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<WooDecision> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<WooDecision> createOne(array $attributes = [])
 * @phpstan-method static Proxy<WooDecision> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<WooDecision> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<WooDecision> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<WooDecision> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<WooDecision> random(array $attributes = [])
 * @phpstan-method static Proxy<WooDecision> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<WooDecision> repository()
 * @phpstan-method static list<Proxy<WooDecision>> all()
 * @phpstan-method static list<Proxy<WooDecision>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<WooDecision>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<WooDecision>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<WooDecision>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<WooDecision>> randomSet(int $number, array $attributes = [])
 */
final class WooDecisionFactory extends ModelFactory
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
        /** @var CarbonImmutable $dateOne */
        $dateOne = CarbonImmutable::createFromMutable(self::faker()->dateTimeBetween('01-01-2010', '01-01-2023'));
        $dateOne = $dateOne->firstOfMonth();

        /** @var CarbonImmutable $dateTwo */
        $dateTwo = CarbonImmutable::createFromMutable(self::faker()->dateTimeBetween('01-01-2010', '01-01-2023'));
        $dateTwo = $dateTwo->firstOfMonth();

        if ($dateTwo->isBefore($dateOne)) {
            [$dateOne, $dateTwo] = [$dateTwo, $dateOne];
        }

        return [
            'dossierNr' => self::faker()->bothify('DOSSIER-####-#####'),
            'title' => self::faker()->sentence(),
            'summary' => self::faker()->sentences(4, true),
            'documentPrefix' => 'PREF',
            'publicationReason' => self::faker()->randomElement(PublicationReason::cases()),
            'decision' => self::faker()->randomElement([
                DecisionType::ALREADY_PUBLIC,
                DecisionType::NOT_PUBLIC,
                DecisionType::NOTHING_FOUND,
                DecisionType::PARTIAL_PUBLIC,
                DecisionType::PARTIAL_PUBLIC,
                DecisionType::PARTIAL_PUBLIC,
                DecisionType::PARTIAL_PUBLIC,
                DecisionType::PUBLIC,
                DecisionType::PUBLIC,
                DecisionType::PUBLIC,
                DecisionType::PUBLIC,
            ]),
            'status' => DossierStatus::PUBLISHED,
            'organisation' => OrganisationFactory::new(),
            'dateFrom' => $dateOne,
            'dateTo' => $dateTwo,
            'decisionDate' => $dateOne,
            'publicationDate' => $dateTwo,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(WooDecision $wooDecision): void {})
        ;
    }

    protected static function getClass(): string
    {
        return WooDecision::class;
    }
}
