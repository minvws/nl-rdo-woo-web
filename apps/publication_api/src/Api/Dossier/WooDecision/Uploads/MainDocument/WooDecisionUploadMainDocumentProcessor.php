<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Uploads\MainDocument;

use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentProcessor;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(bind: [
    '$dossierRepositoryWithExternalId' => '@' . WooDecisionRepository::class,
    '$mainDocumentClass' => WooDecisionMainDocument::class,
])]
final readonly class WooDecisionUploadMainDocumentProcessor extends UploadMainDocumentProcessor
{
}
