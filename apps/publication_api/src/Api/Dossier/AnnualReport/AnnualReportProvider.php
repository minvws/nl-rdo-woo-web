<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\AnnualReport;

use PublicationApi\Api\Dossier\DossierProvider;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @extends DossierProvider<AnnualReport,AnnualReportResponseDto>
 */
#[Autoconfigure(bind: [
    '$abstractDossierRepository' => '@' . AnnualReportRepository::class,
    '$dossierResponseMapper' => '@' . AnnualReportResponseMapper::class,
    '$entityName' => 'AnnualReport',
])]
final readonly class AnnualReportProvider extends DossierProvider
{
}
