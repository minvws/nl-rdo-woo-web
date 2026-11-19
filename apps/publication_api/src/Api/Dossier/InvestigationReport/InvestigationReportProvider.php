<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport;

use PublicationApi\Api\Dossier\DossierProvider;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @extends DossierProvider<InvestigationReport,InvestigationReportResponseDto>
 */
#[Autoconfigure(bind: [
    '$abstractDossierRepository' => '@' . InvestigationReportRepository::class,
    '$dossierResponseMapper' => '@' . InvestigationReportResponseMapper::class,
    '$entityName' => 'InvestigationReport',
])]
final readonly class InvestigationReportProvider extends DossierProvider
{
}
