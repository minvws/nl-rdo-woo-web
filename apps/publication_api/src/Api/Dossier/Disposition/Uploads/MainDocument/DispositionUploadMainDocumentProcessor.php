<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition\Uploads\MainDocument;

use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentProcessor;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(bind: [
    '$dossierRepositoryWithExternalId' => '@' . DispositionRepository::class,
    '$mainDocumentClass' => DispositionMainDocument::class,
])]
final readonly class DispositionUploadMainDocumentProcessor extends UploadMainDocumentProcessor
{
}
