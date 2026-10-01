<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\AnnualReport;

use PublicationApi\Api\Dossier\DossierProcessor;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @extends DossierProcessor<AnnualReport,AnnualReportRequestDto,AnnualReportResponseDto>
 */
#[Autoconfigure(bind: [
    '$dossierStrategy' => '@' . AnnualReportStrategy::class,
    '$dossierResponseMapper' => '@' . AnnualReportResponseMapper::class,
])]
final readonly class AnnualReportProcessor extends DossierProcessor
{
}
