<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\AnnualReport\Uploads\MainDocument;

use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentProcessor;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(bind: [
    '$dossierRepositoryWithExternalId' => '@' . AnnualReportRepository::class,
    '$mainDocumentClass' => AnnualReportMainDocument::class,
])]
final readonly class AnnualReportUploadMainDocumentProcessor extends UploadMainDocumentProcessor
{
}
