<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition\Uploads\Attachment;

use PublicationApi\Api\Uploads\Attachment\UploadAttachmentProcessor;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(bind: [
    '$dossierRepositoryWithExternalId' => '@' . DispositionRepository::class,
    '$attachmentClass' => DispositionAttachment::class,
])]
final readonly class DispositionUploadAttachmentProcessor extends UploadAttachmentProcessor
{
}
