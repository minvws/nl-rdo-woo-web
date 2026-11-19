<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication\Uploads\Attachment;

use PublicationApi\Api\Uploads\Attachment\UploadAttachmentProcessor;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationAttachment;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(bind: [
    '$dossierRepositoryWithExternalId' => '@' . OtherPublicationRepository::class,
    '$attachmentClass' => OtherPublicationAttachment::class,
])]
final readonly class OtherPublicationUploadAttachmentProcessor extends UploadAttachmentProcessor
{
}
