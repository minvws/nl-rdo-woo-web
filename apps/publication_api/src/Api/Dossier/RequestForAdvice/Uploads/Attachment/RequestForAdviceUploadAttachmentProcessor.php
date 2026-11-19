<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice\Uploads\Attachment;

use PublicationApi\Api\Uploads\Attachment\UploadAttachmentProcessor;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceAttachment;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(bind: [
    '$dossierRepositoryWithExternalId' => '@' . RequestForAdviceRepository::class,
    '$attachmentClass' => RequestForAdviceAttachment::class,
])]
final readonly class RequestForAdviceUploadAttachmentProcessor extends UploadAttachmentProcessor
{
}
