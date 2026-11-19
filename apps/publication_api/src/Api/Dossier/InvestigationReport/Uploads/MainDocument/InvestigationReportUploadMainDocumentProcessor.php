<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport\Uploads\MainDocument;

use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentProcessor;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(bind: [
    '$dossierRepositoryWithExternalId' => '@' . InvestigationReportRepository::class,
    '$mainDocumentClass' => InvestigationReportMainDocument::class,
])]
final readonly class InvestigationReportUploadMainDocumentProcessor extends UploadMainDocumentProcessor
{
}
