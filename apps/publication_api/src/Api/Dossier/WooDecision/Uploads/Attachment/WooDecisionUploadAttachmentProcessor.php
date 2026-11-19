<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Uploads\Attachment;

use PublicationApi\Api\Uploads\Attachment\UploadAttachmentProcessor;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(bind: [
    '$dossierRepositoryWithExternalId' => '@' . WooDecisionRepository::class,
    '$attachmentClass' => WooDecisionAttachment::class,
])]
final readonly class WooDecisionUploadAttachmentProcessor extends UploadAttachmentProcessor
{
}
