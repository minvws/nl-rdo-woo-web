<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport;

use PublicationApi\Api\Dossier\DossierProcessor;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @extends DossierProcessor<InvestigationReport,InvestigationReportRequestDto,InvestigationReportResponseDto>
 */
#[Autoconfigure(bind: [
    '$dossierStrategy' => '@' . InvestigationReportStrategy::class,
    '$dossierResponseMapper' => '@' . InvestigationReportResponseMapper::class,
])]
final readonly class InvestigationReportProcessor extends DossierProcessor
{
}
