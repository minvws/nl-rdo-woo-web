<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement\Uploads\MainDocument;

use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentProcessor;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(bind: [
    '$dossierRepositoryWithExternalId' => '@' . ComplaintJudgementRepository::class,
    '$mainDocumentClass' => ComplaintJudgementMainDocument::class,
])]
final readonly class ComplaintJudgementUploadMainDocumentProcessor extends UploadMainDocumentProcessor
{
}
