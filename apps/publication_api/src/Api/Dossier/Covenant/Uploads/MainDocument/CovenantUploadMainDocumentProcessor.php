<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant\Uploads\MainDocument;

use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentProcessor;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(bind: [
    '$dossierRepositoryWithExternalId' => '@' . CovenantRepository::class,
    '$mainDocumentClass' => CovenantMainDocument::class,
])]
final readonly class CovenantUploadMainDocumentProcessor extends UploadMainDocumentProcessor
{
}
