<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Advice\Uploads\Attachment;

use PublicationApi\Api\Uploads\Attachment\UploadAttachmentProcessor;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceAttachment;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(bind: [
    '$dossierRepositoryWithExternalId' => '@' . AdviceRepository::class,
    '$attachmentClass' => AdviceAttachment::class,
])]
final readonly class AdviceUploadAttachmentProcessor extends UploadAttachmentProcessor
{
}
