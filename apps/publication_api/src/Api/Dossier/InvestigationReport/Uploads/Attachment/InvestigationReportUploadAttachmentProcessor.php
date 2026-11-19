<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport\Uploads\Attachment;

use PublicationApi\Api\Uploads\Attachment\UploadAttachmentProcessor;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(bind: [
    '$dossierRepositoryWithExternalId' => '@' . InvestigationReportRepository::class,
    '$attachmentClass' => InvestigationReportAttachment::class,
])]
final readonly class InvestigationReportUploadAttachmentProcessor extends UploadAttachmentProcessor
{
}
