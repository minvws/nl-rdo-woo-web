<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Advice\Uploads\MainDocument;

use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentProcessor;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceMainDocument;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(bind: [
    '$dossierRepositoryWithExternalId' => '@' . AdviceRepository::class,
    '$mainDocumentClass' => AdviceMainDocument::class,
])]
final readonly class AdviceUploadMainDocumentProcessor extends UploadMainDocumentProcessor
{
}
