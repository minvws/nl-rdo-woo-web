<?php

namespace App\Tests\Factory\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Tests\Factory\OrganisationFactory;
use Carbon\CarbonImmutable;

/**
 * @method        \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision|\Zenstruck\Foundry\Persistence\Proxy                                                     create(array|callable $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision|\Zenstruck\Foundry\Persistence\Proxy                                                     createOne(array $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision|\Zenstruck\Foundry\Persistence\Proxy                                                     find(object|array|mixed $criteria)
 * @method static \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision|\Zenstruck\Foundry\Persistence\Proxy                                                     findOrCreate(array $attributes)
 * @method static \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision|\Zenstruck\Foundry\Persistence\Proxy                                                     first(string $sortedField = 'id')
 * @method static \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision|\Zenstruck\Foundry\Persistence\Proxy                                                     last(string $sortedField = 'id')
 * @method static \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision|\Zenstruck\Foundry\Persistence\Proxy                                                     random(array $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision|\Zenstruck\Foundry\Persistence\Proxy                                                     randomOrCreate(array $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 all()
 * @method static \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createMany(int $number, array|callable $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 createSequence(iterable|callable $sequence)
 * @method static \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 findBy(array $attributes)
 * @method static \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomRange(int $min, int $max, array $attributes = [])
 * @method static \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision[]|\Zenstruck\Foundry\Persistence\Proxy[]                                                 randomSet(int $number, array $attributes = [])
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision|\Zenstruck\Foundry\Persistence\Proxy>               many(int $min, int|null $max = null)
 * @method        \Zenstruck\Foundry\FactoryCollection<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision|\Zenstruck\Foundry\Persistence\Proxy>               sequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision, \Doctrine\ORM\EntityRepository> repository()
 *
 * @phpstan-method \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision> create(array|callable $attributes = [])
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision> createOne(array $attributes = [])
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision> find(object|array|mixed $criteria)
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision> findOrCreate(array $attributes)
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision> first(string $sortedField = 'id')
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision> last(string $sortedField = 'id')
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision> random(array $attributes = [])
 * @phpstan-method static \App\Domain\Publication\Dossier\Type\WooDecision\WooDecision&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision>> all()
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision>> findBy(array $attributes)
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision>> many(int $min, int|null $max = null)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision&\Zenstruck\Foundry\Persistence\Proxy<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision>> sequence(iterable|callable $sequence)
 *
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\App\Domain\Publication\Dossier\Type\WooDecision\WooDecision>
 */
final class WooDecisionFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
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
     * @return array<string, mixed>
     */
    protected function defaults(): array
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
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(WooDecision $wooDecision): void {})
        ;
    }

    public static function class(): string
    {
        return WooDecision::class;
    }
}
