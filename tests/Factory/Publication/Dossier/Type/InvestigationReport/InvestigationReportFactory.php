<?php

declare(strict_types=1);

namespace Shared\Tests\Factory\Publication\Dossier\Type\InvestigationReport;

use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\ValueObject\DossierTitle;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<InvestigationReport>
 */
final class InvestigationReportFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        $publicationDate = self::faker()->plainDateBetween('01-01-2010', '01-01-2023');

        return [
            'dossierNr' => self::faker()->bothify('DOSSIER-####-#####'),
            'title' => DossierTitle::create(self::faker()->sentence()),
            'summary' => self::faker()->sentences(4, true),
            'documentPrefix' => WooDecisionFactory::DEFAULT_PREFIX,
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
