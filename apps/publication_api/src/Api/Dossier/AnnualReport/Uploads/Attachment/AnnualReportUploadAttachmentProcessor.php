<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\AnnualReport\Uploads\Attachment;

use PublicationApi\Api\Uploads\Attachment\UploadAttachmentProcessor;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(bind: [
    '$dossierRepositoryWithExternalId' => '@' . AnnualReportRepository::class,
    '$attachmentClass' => AnnualReportAttachment::class,
])]
final readonly class AnnualReportUploadAttachmentProcessor extends UploadAttachmentProcessor
{
}
