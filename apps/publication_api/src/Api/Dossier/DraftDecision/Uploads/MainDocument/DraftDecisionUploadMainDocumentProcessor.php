<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision\Uploads\MainDocument;

use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentProcessor;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(bind: [
    '$dossierRepositoryWithExternalId' => '@' . DraftDecisionRepository::class,
    '$mainDocumentClass' => DraftDecisionMainDocument::class,
])]
final readonly class DraftDecisionUploadMainDocumentProcessor extends UploadMainDocumentProcessor
{
}
