<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant\Uploads\Attachment;

use PublicationApi\Api\Uploads\Attachment\UploadAttachmentProcessor;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(bind: [
    '$dossierRepositoryWithExternalId' => '@' . CovenantRepository::class,
    '$attachmentClass' => CovenantAttachment::class,
])]
final readonly class CovenantUploadAttachmentProcessor extends UploadAttachmentProcessor
{
}
