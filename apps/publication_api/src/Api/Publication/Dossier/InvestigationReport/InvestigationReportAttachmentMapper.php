<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\InvestigationReport;

use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
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
        $attachment->setGrounds($attachmentRequestDto->grounds);
        $attachment->setExternalId($attachmentRequestDto->externalId);

        return $attachment;
    }
}
