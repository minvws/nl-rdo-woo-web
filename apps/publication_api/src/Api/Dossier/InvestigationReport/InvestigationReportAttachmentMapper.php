<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;

class InvestigationReportAttachmentMapper
{
    public static function create(
        InvestigationReport $investigationReport,
        AttachmentRequestDto $attachmentRequestDto,
    ): InvestigationReportAttachment {
        $attachment = new InvestigationReportAttachment(
            $investigationReport,
            $attachmentRequestDto->formalDate,
            $attachmentRequestDto->type,
            $attachmentRequestDto->language,
        );

        $attachment->getFileInfo()->setName($attachmentRequestDto->fileName->toString());
        $attachment->setGrounds($attachmentRequestDto->grounds);
        $attachment->setExternalId($attachmentRequestDto->externalId);

        return $attachment;
    }
}
