<?php

declare(strict_types=1);

namespace Shared\Tests\Factory\Publication\Dossier\Type\InvestigationReport;

use Carbon\CarbonImmutable;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Tests\Factory\OrganisationFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<InvestigationReport>
 */
final class InvestigationReportFactory extends PersistentProxyObjectFactory
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
