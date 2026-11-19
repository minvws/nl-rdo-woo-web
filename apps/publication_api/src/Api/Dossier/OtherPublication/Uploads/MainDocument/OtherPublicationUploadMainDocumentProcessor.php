<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication\Uploads\MainDocument;

use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentProcessor;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(bind: [
    '$dossierRepositoryWithExternalId' => '@' . OtherPublicationRepository::class,
    '$mainDocumentClass' => OtherPublicationMainDocument::class,
])]
final readonly class OtherPublicationUploadMainDocumentProcessor extends UploadMainDocumentProcessor
{
}
