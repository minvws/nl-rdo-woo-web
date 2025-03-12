<?php

declare(strict_types=1);

namespace App\Tests\Factory\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Tests\Factory\OrganisationFactory;
use Carbon\CarbonImmutable;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<WooDecision>
 */
final class WooDecisionFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        $dateOne = CarbonImmutable::createFromMutable(self::faker()->dateTimeBetween('01-01-2010', '01-01-2023'));
        $dateOne = $dateOne->firstOfMonth();

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
        return $this;
    }

    public static function class(): string
    {
        return WooDecision::class;
    }
}
