<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision\Uploads\Attachment;

use PublicationApi\Api\Uploads\Attachment\UploadAttachmentProcessor;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(bind: [
    '$dossierRepositoryWithExternalId' => '@' . DraftDecisionRepository::class,
    '$attachmentClass' => DraftDecisionAttachment::class,
])]
final readonly class DraftDecisionUploadAttachmentProcessor extends UploadAttachmentProcessor
{
}
