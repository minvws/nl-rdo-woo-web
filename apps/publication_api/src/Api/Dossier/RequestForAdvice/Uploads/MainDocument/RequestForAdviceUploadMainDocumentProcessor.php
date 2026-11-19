<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice\Uploads\MainDocument;

use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentProcessor;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocument;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(bind: [
    '$dossierRepositoryWithExternalId' => '@' . RequestForAdviceRepository::class,
    '$mainDocumentClass' => RequestForAdviceMainDocument::class,
])]
final readonly class RequestForAdviceUploadMainDocumentProcessor extends UploadMainDocumentProcessor
{
}
