<?php

declare(strict_types=1);

namespace Shared\Tests\Factory\Publication\Dossier\Type\WooDecision;

use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\ValueObject\PlainDate;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<WooDecision>
 */
final class WooDecisionFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        $dateOne = PlainDate::create(self::faker()->dateTimeBetween('01-01-2010', '01-01-2023')->format('Y-m-d'));
        $dateOne = $dateOne->firstOfMonth();

        $dateTwo = PlainDate::create(self::faker()->dateTimeBetween('01-01-2010', '01-01-2023')->format('Y-m-d'));
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

    public function concept(): self
    {
        return $this->with([
            'status' => DossierStatus::CONCEPT,
            'mainDocument' => null,
        ]);
    }

    public function scheduled(): self
    {
        return $this->with([
            'departments' => [DepartmentFactory::new()],
            'mainDocument' => WooDecisionMainDocumentFactory::new(),
            'status' => DossierStatus::SCHEDULED,
            'previewDate' => self::faker()->optional()->dateTimeBetween('+1 week', '+2 weeks'),
            'publicationDate' => self::faker()->plainDateBetween('+1 week', '+2 weeks'),
        ]);
    }

    public static function class(): string
    {
        return WooDecision::class;
    }
}
